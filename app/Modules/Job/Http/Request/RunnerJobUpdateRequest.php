<?php

namespace App\Modules\Job\Http\Request;

class RunnerJobUpdateRequest extends UpdateJobRequest
{
    /**
     * Largest status_message we accept, in BYTES.
     *
     * status_message is a MySQL TEXT column: 65535 bytes, not characters. The
     * runner started attaching a real diagnostic to it on 2026-08-27 (until
     * then it was always one of ~7 fixed enum strings), so the field can now
     * carry an arbitrary exception message.
     */
    public const STATUS_MESSAGE_MAX = 60000;

    /**
     * Largest decision_report we store, as encoded JSON, in BYTES.
     *
     * Same reasoning as STATUS_MESSAGE_MAX and the same answer: shrink, never
     * reject. The unbounded member is warnings[], which carries one exception
     * message per failed filter.
     */
    public const DECISION_REPORT_MAX = 60000;

    /**
     * decision_selection_mode is varchar(32) and holds "llm" or "random".
     */
    public const DECISION_SELECTION_MODE_MAX = 32;

    /**
     * Report members dropped, in this order, to fit DECISION_REPORT_MAX.
     *
     * Worst first: warnings is the only unbounded one, applied/requested are
     * short lists of filter names, llm is three fields, and filters -- which
     * names WHICH part of the treatment did not happen -- is the last thing
     * worth losing.
     */
    public const DECISION_REPORT_SHRINK_ORDER = ['warnings', 'requested', 'applied', 'llm', 'filters'];

    /**
     * Both models are $guarded = [], so whatever survives validation here is
     * mass-assigned. Every field used to be a bare ['nullable'] with no type
     * and no length.
     *
     * Deliberately NOT an in: whitelist on status_code, and deliberately not a
     * date rule on the timestamps: a rejected update returns 422, the runner
     * logs the failure and moves on (JobService::updateJobState swallows it),
     * and the job is then stuck at "running" for ever. Rules here may only
     * reject what could not possibly be a real runner payload.
     *
     * The decision_* fields have the same posture, harder. They are the
     * decision function's verdict riding on the SAME PUT as status_code
     * (docs/integration-contract.md 7.3), so a 422 over a malformed verdict
     * would cost the job its terminal state as well as its verdict. They are
     * therefore coerced in prepareForValidation() -- "true"/1 to a boolean, a
     * JSON string to an array, an over-long mode truncated -- and anything that
     * cannot be coerced is DROPPED rather than rejected. Dropped means the
     * column stays NULL, which is the contract's "unknown": the honest answer
     * for a verdict we could not read, and never mistaken for "clean".
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'queue_id' => ['nullable', 'integer'],
            'type' => ['nullable', 'string', 'max:255'],
            'status_code' => ['nullable', 'string', 'max:255'],
            'status_message' => ['nullable', 'string', 'max:'.self::STATUS_MESSAGE_MAX],
            'order' => ['nullable', 'integer'],
            'active' => ['nullable', 'boolean'],
            // started_at/finished_at are varchar(255) columns and the runner
            // writes an ISO-8601 string into them verbatim ("2026-08-27T07:54:03.008+0200").
            // Length is what the column needs protecting from; parseability is
            // not worth a 422.
            'started_at' => ['nullable', 'string', 'max:255'],
            'finished_at' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer'],
            'captcha_found' => ['nullable', 'boolean'],
            'captcha_solved' => ['nullable', 'boolean'],
            // The verdict. Every rule below is satisfied by construction after
            // prepareForValidation(); they are here to describe the columns,
            // not to police the runner.
            'decision_degraded' => ['nullable', 'boolean'],
            'decision_treatment_lost' => ['nullable', 'boolean'],
            'decision_selection_mode' => ['nullable', 'string', 'max:'.self::DECISION_SELECTION_MODE_MAX],
            'decision_report' => ['nullable', 'array'],
        ];
    }

    /**
     * Coerce what can be coerced and drop what cannot, so the status update
     * itself always lands.
     *
     * Losing a verdict costs one job's worth of "did the treatment apply?".
     * Losing the status PUT leaves the job at "running" for ever and takes the
     * run's own rollup with it. The asymmetry is the whole design.
     */
    protected function prepareForValidation(): void
    {
        $this->truncateStatusMessage();

        foreach (['decision_degraded', 'decision_treatment_lost'] as $flag) {
            $this->normaliseBoolean($flag);
        }

        $this->normaliseSelectionMode();
        $this->normaliseReport();
    }

    /**
     * Truncate an over-long diagnostic instead of rejecting the update.
     *
     * Losing the job's terminal state is far worse than losing the tail of a
     * stack trace. Cut on bytes (mb_strcut keeps multibyte characters whole) so
     * the result fits the TEXT column; the max: rule counts characters, so a
     * value trimmed to 60000 bytes always passes it.
     */
    private function truncateStatusMessage(): void
    {
        $message = $this->input('status_message');

        if (is_string($message) && mb_strlen($message, '8bit') > self::STATUS_MESSAGE_MAX) {
            $this->merge([
                'status_message' => mb_strcut($message, 0, self::STATUS_MESSAGE_MAX - 12).' [truncated]',
            ]);
        }
    }

    /**
     * A verdict flag as a real boolean, or dropped.
     *
     * Wider than Laravel's boolean rule on purpose: the runner serialises these
     * from Java and a proxy or a hand-rolled curl may well send "true".
     * Anything filter_var cannot read as a boolean -- an object, "maybe" --
     * leaves the field absent, i.e. NULL, i.e. unknown.
     */
    private function normaliseBoolean(string $field): void
    {
        if ( ! $this->has($field)) {
            return;
        }

        $value = $this->input($field);

        if (null === $value) {
            // An explicit null is a verdict of "unknown" and is stored as one.
            // OMITTING the field is different and is handled by has() above:
            // a runner re-reporting a job without a verdict must not erase the
            // verdict an earlier PUT recorded.
            return;
        }

        $coerced = is_scalar($value)
            ? filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;

        if (null === $coerced) {
            $this->drop($field);

            return;
        }

        $this->merge([$field => $coerced]);
    }

    /**
     * The selection mode, truncated to the column, or dropped.
     *
     * An unrecognised mode is STORED, not rejected and not normalised: the
     * column is a description of how the video was picked, no consumer may
     * branch on it alone, and a value the API has not heard of is exactly the
     * kind of thing an operator needs to be able to see in the row.
     */
    private function normaliseSelectionMode(): void
    {
        $field = 'decision_selection_mode';

        if ( ! $this->has($field)) {
            return;
        }

        $mode = $this->input($field);

        if (null === $mode) {
            return;
        }

        if ( ! is_scalar($mode)) {
            $this->drop($field);

            return;
        }

        $this->merge([$field => mb_strcut((string) $mode, 0, self::DECISION_SELECTION_MODE_MAX)]);
    }

    /**
     * The report as an array that fits the column, or dropped.
     *
     * Accepts it as a JSON string too -- an HTTP client that encodes a nested
     * object once too often is not a reason to lose a verdict.
     */
    private function normaliseReport(): void
    {
        $field = 'decision_report';

        if ( ! $this->has($field)) {
            return;
        }

        $report = $this->input($field);

        if (null === $report) {
            return;
        }

        if (is_string($report)) {
            $report = json_decode($report, true);
        }

        if ( ! is_array($report)) {
            $this->drop($field);

            return;
        }

        $this->merge([$field => $this->shrinkReport($report)]);
    }

    /**
     * Drop report members until the encoded JSON fits, worst first.
     *
     * Not a byte truncation: half a JSON document is not a JSON document, and
     * the column is typed. What survives is marked _truncated so a reader can
     * tell a short report from a shortened one -- silently serving a report
     * with its warnings removed as if that were all the decision function said
     * is the failure mode this guard exists to avoid.
     *
     * @param  array<string, mixed> $report
     * @return array<string, mixed>
     */
    private function shrinkReport(array $report): array
    {
        if ( ! $this->reportIsOversized($report)) {
            return $report;
        }

        $report['_truncated'] = true;

        foreach (self::DECISION_REPORT_SHRINK_ORDER as $member) {
            unset($report[$member]);

            if ( ! $this->reportIsOversized($report)) {
                return $report;
            }
        }

        // Everything the contract knows about is gone and it still does not
        // fit, so what remains is a member nobody here can size. Keep the fact
        // that a report existed and nothing else.
        return ['_truncated' => true];
    }

    /**
     * @param array<string, mixed> $report
     */
    private function reportIsOversized(array $report): bool
    {
        $encoded = json_encode($report);

        return false === $encoded || mb_strlen($encoded, '8bit') > self::DECISION_REPORT_MAX;
    }

    /**
     * Remove a field from the request so it is never mass-assigned.
     *
     * The column then keeps whatever it held -- NULL for a fresh job, which is
     * the contract's "unknown".
     */
    private function drop(string $field): void
    {
        $this->getInputSource()->remove($field);
    }
}
