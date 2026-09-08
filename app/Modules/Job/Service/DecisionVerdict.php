<?php

namespace App\Modules\Job\Service;

/**
 * What one decision-function response says about its own health.
 *
 * This is the PHP copy of the lambda_* helpers in the orchestrator's
 * orchestrator/status.py, and of docs/integration-contract.md 7.3. Keep the
 * three in sync: they are three implementations of one contract, and the whole
 * point of the contract is that a job row, a MinIO object and an analysis
 * script cannot disagree about whether a treatment applied.
 *
 * The decision function answers HTTP 200 even when every filter it was asked
 * for failed. The status code tells you the CALL worked; it never tells you the
 * TREATMENT worked. Three fields carry that, they answer three different
 * questions, and conflating any two of them is the mistake this contract was
 * amended to prevent:
 *
 * - degraded        a REQUESTED filter failed to apply, or nothing could be
 *                   selected. "A human should look at this decision."
 * - treatment_lost  degraded AND nothing requested applied -- a bot configured
 *                   to be treated produced control-arm data. "This session's
 *                   data is scientifically void." The only alarm.
 * - selectionMode   "llm" / "random". A NEUTRAL DESCRIPTION of how the video
 *                   was picked. Never branch on it alone, here or anywhere.
 *
 * An earlier draft of the contract treated selection_mode === 'random' as proof
 * that a treatment arm had silently become a control arm. It is not: a genuine
 * control crawler (pi=psi=kappa=tau=0) requests no LLM filter at all, so random
 * is its correct output. Measured on ex63 before this shipped, 295 of 358
 * lambda payloads carry no rating column and 236 of 243 bot configurations are
 * control-arm, so that gate would have marked ~82% of every lambdaselect job
 * degraded and held a critical alert on permanently.
 *
 * "Clean" and "we cannot tell" are different answers and a boolean cannot carry
 * both, which is why $reported exists. A payload with no degradation report at
 * all came from a decision function deployed before the report existed, and the
 * archive is full of them; it is UNKNOWN, never degraded and never clean.
 */
final class DecisionVerdict
{
    /**
     * The six filters the decision function reports on, in the order it emits
     * them. Used to render findings in a stable order; an unknown key is
     * carried along rather than dropped.
     */
    public const FILTER_NAMES = [
        'already_watched',
        'mainstream_media',
        'children_entertainment',
        'similar',
        'political',
        'fringe',
    ];

    /**
     * Filter states that are NOT failures.
     *
     * - 'ok'              requested, and it applied.
     * - 'skipped'         NOT REQUESTED. The normal state of four or five of
     *                     the six filters on every healthy control invocation,
     *                     and counting it as a failure is the single most
     *                     common way to turn this contract back into a
     *                     false-alarm generator.
     * - 'not_implemented' the filter is a stub that cannot do anything yet and
     *                     the decision function says so honestly instead of
     *                     lying with 'ok' (lambda_function.py:
     *                     filters['already_watched'] = 'not_implemented', on the
     *                     unconditional success path). Nothing went wrong;
     *                     nothing was ever going to happen. already_watched is
     *                     always-on hygiene, never a configured treatment, so it
     *                     is also absent from the function's own
     *                     requested_treatments denominator and can neither
     *                     degrade a decision nor lose a treatment. The function's
     *                     own gate agrees: filter_failed is
     *                     any(str(status).startswith('failed')).
     * - ''                nothing was said. Unknown, and unknown is not a finding.
     *
     * Anything else is treated as a failure so an unrecognised FUTURE state errs
     * towards being seen.
     *
     * 'not_implemented' was missing from this list until 2026-09-08, and it is
     * present in every payload the deployed decision function has produced since
     * 20260902-144403 (32 of 32 archived payloads, 31 of them with
     * degraded: false). Without it, meta.verdict.failed_filters reported one
     * failed filter on 100% of healthy lambdaselect jobs -- the same
     * always-on-alarm failure mode 'skipped' is called out for above.
     *
     * This mirrors orchestrator/status.py's _FILTER_OK_STATES, which is the copy
     * this one and the runner's LambdaSelectJobResult are written against
     * (docs/integration-contract.md 7.3). The three must agree.
     */
    public const FILTER_OK_STATES = ['ok', 'skipped', 'not_implemented', ''];

    /**
     * The keys of the decision payload that are kept in jobs.decision_report.
     *
     * 'filters' and 'llm' say which part of the treatment did not happen;
     * 'warnings' is what the decision function itself chose to say about the
     * invocation; 'applied'/'requested' are emitted by newer versions.
     */
    public const REPORT_KEYS = ['filters', 'llm', 'warnings', 'applied', 'requested'];

    /**
     * @param array<string, string> $filters
     * @param string[]              $warnings
     * @param array<string, mixed>  $report
     */
    private function __construct(
        public readonly bool $reported,
        public readonly bool $degraded,
        public readonly bool $treatmentLost,
        public readonly string $selectionMode,
        public readonly array $filters,
        public readonly array $warnings,
        public readonly ?string $error,
        public readonly array $report,
    ) {}

    /**
     * Read one decision-function response.
     *
     * @param array<string, mixed>|null $payload
     */
    public static function fromPayload(?array $payload): self
    {
        $payload ??= [];

        $filters = self::filtersOf($payload);
        $warnings = self::warningsOf($payload);
        $mode = self::selectionModeOf($payload);
        $degraded = self::degradedOf($payload, $filters, $warnings);

        return new self(
            reported: self::reportPresent($payload),
            degraded: $degraded,
            treatmentLost: self::treatmentLostOf($payload, $degraded, $mode),
            selectionMode: $mode,
            filters: $filters,
            warnings: $warnings,
            error: self::errorOf($payload),
            report: self::reportOf($payload),
        );
    }

    /**
     * True when the payload carries a degradation report of any version.
     *
     * False means the answer to "did this decision degrade?" is UNKNOWN, not
     * "no". It is what keeps a legacy payload out of the columns entirely --
     * see JobDecisionService::columnsFor(), which writes nothing when this is
     * false rather than writing false.
     *
     * selection_mode alone is deliberately not enough: it is a descriptor, and
     * a payload carrying only a mode still cannot say whether anything
     * degraded.
     *
     * @param array<string, mixed> $payload
     */
    public static function reportPresent(array $payload): bool
    {
        foreach (['degraded', 'treatment_lost', 'filters'] as $key) {
            if (array_key_exists($key, $payload)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filters that were requested and did not apply.
     *
     * The only correct way to count filter casualties: 'skipped' means NOT
     * REQUESTED, and on a control crawler five of the six filters are skipped
     * on every single healthy invocation.
     *
     * @return array<string, string>
     */
    public function failedFilters(): array
    {
        return array_filter(
            $this->filters,
            fn (string $state): bool => ! in_array(mb_strtolower(trim($state)), self::FILTER_OK_STATES, true)
        );
    }

    /**
     * Filters that were never requested. Informational; never a finding.
     *
     * @return string[]
     */
    public function skippedFilters(): array
    {
        return array_keys(array_filter(
            $this->filters,
            fn (string $state): bool => 'skipped' === mb_strtolower(trim($state))
        ));
    }

    /**
     * One word for a table cell or a log line.
     */
    public function label(): string
    {
        return match (true) {
            null !== $this->error => 'error',
            $this->treatmentLost => 'treatment-lost',
            $this->degraded => 'degraded',
            ! $this->reported => 'no report',
            default => 'ok',
        };
    }

    /**
     * A *requested* filter failed, or selection failed outright.
     *
     * Reads the explicit flag. For payloads that predate it, falls back to
     * evidence the decision function itself produced -- an explicit
     * "failed: ..." filter state, or a non-empty warnings list, which it only
     * writes when something it tried to do did not work.
     *
     * It deliberately does NOT fall back to selection_mode === 'random'.
     *
     * @param array<string, mixed>  $payload
     * @param array<string, string> $filters
     * @param string[]              $warnings
     */
    private static function degradedOf(array $payload, array $filters, array $warnings): bool
    {
        if (array_key_exists('degraded', $payload)) {
            return (bool) $payload['degraded'];
        }

        // treatment_lost implies degraded; a payload carrying only the newer
        // field is still self-consistent.
        if ( ! empty($payload['treatment_lost'])) {
            return true;
        }

        foreach ($filters as $state) {
            if ( ! in_array(mb_strtolower(trim($state)), self::FILTER_OK_STATES, true)) {
                return true;
            }
        }

        return [] !== $warnings;
    }

    /**
     * The bot was configured to be treated and produced control-arm data.
     *
     * The decision function computes this at the source precisely so that no
     * consumer has to combine two terms and get it wrong. For payloads written
     * after 'degraded' but before 'treatment_lost' it recomputes the same two
     * terms -- degraded AND nothing requested applied -- never selection_mode
     * alone. For a payload with no report it is false: unknown is not an alarm.
     *
     * @param array<string, mixed> $payload
     */
    private static function treatmentLostOf(array $payload, bool $degraded, string $selectionMode): bool
    {
        if (array_key_exists('treatment_lost', $payload)) {
            return (bool) $payload['treatment_lost'];
        }

        if (array_key_exists('degraded', $payload)) {
            return $degraded && 'random' === $selectionMode;
        }

        return false;
    }

    /**
     * The per-filter report as strings, contract order first, unknown keys last.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    private static function filtersOf(array $payload): array
    {
        $raw = $payload['filters'] ?? null;

        if ( ! is_array($raw)) {
            return [];
        }

        $ordered = [];

        foreach (self::FILTER_NAMES as $name) {
            if (array_key_exists($name, $raw)) {
                $ordered[$name] = self::scalarToString($raw[$name]);
            }
        }

        foreach ($raw as $name => $state) {
            if ( ! in_array((string) $name, self::FILTER_NAMES, true)) {
                $ordered[(string) $name] = self::scalarToString($state);
            }
        }

        return $ordered;
    }

    /**
     * @param  array<string, mixed> $payload
     * @return string[]
     */
    private static function warningsOf(array $payload): array
    {
        $raw = $payload['warnings'] ?? [];

        if (is_string($raw)) {
            return '' === $raw ? [] : [$raw];
        }

        if ( ! is_array($raw)) {
            return [];
        }

        return array_values(array_map(self::scalarToString(...), $raw));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function selectionModeOf(array $payload): string
    {
        $mode = $payload['selection_mode'] ?? null;

        return is_scalar($mode) ? mb_strtolower(trim((string) $mode)) : '';
    }

    /**
     * The hard error reported by the lambda server, if any.
     *
     * Present only on a 4xx/5xx; a degraded decision is still a 200.
     *
     * @param array<string, mixed> $payload
     */
    private static function errorOf(array $payload): ?string
    {
        $error = $payload['error'] ?? null;

        if (null === $error || '' === $error || [] === $error) {
            return null;
        }

        return self::scalarToString($error);
    }

    /**
     * The members of the payload kept in jobs.decision_report.
     *
     * @param  array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private static function reportOf(array $payload): array
    {
        $report = [];

        foreach (self::REPORT_KEYS as $key) {
            if (array_key_exists($key, $payload)) {
                $report[$key] = $payload[$key];
            }
        }

        return $report;
    }

    private static function scalarToString(mixed $value): string
    {
        if (is_scalar($value)) {
            return (string) $value;
        }

        return (string) json_encode($value);
    }
}
