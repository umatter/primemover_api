<?php

namespace Tests\Feature\Modules\Job\Service;

use App\Modules\Job\Service\DecisionVerdict;
use Tests\TestCase;

/**
 * The PHP copy of the decision-function contract.
 *
 * These assertions are the contract, not an implementation detail: every one of
 * them has an exact counterpart in orchestrator/status.py, and the reason there
 * are three implementations of one rule set is that a job row, a MinIO object
 * and an analysis script must never disagree about whether a treatment applied.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Job\Service\DecisionVerdict
 */
class DecisionVerdictTest extends TestCase
{
    /**
     * The single most important negative case in the whole contract.
     *
     * A control crawler (pi=psi=kappa=tau=0) requests no LLM filter, draws
     * uniformly and correctly reports selection_mode 'random'. Nothing went
     * wrong; nothing was requested. An earlier draft of the contract read that
     * as a lost treatment, which -- measured on ex63 -- would have marked ~82%
     * of every lambdaselect job degraded and pinned a critical alert on
     * permanently over entirely healthy traffic.
     *
     * @covers ::fromPayload
     */
    public function test_random_selection_alone_is_never_degradation(): void
    {
        $verdict = DecisionVerdict::fromPayload([
            'degraded' => false,
            'treatment_lost' => false,
            'selection_mode' => 'random',
            'filters' => ['political' => 'skipped', 'fringe' => 'skipped'],
        ]);

        $this->assertTrue($verdict->reported);
        $this->assertFalse($verdict->degraded);
        $this->assertFalse($verdict->treatmentLost);
        $this->assertSame('random', $verdict->selectionMode);
        $this->assertSame('ok', $verdict->label());
    }

    /**
     * 'skipped' means NOT REQUESTED. On a healthy control invocation five of
     * the six filters are skipped, so counting them as casualties is the single
     * most common way to turn this contract back into a false-alarm generator.
     *
     * @covers ::failedFilters
     * @covers ::skippedFilters
     */
    public function test_skipped_filters_are_not_failures(): void
    {
        $verdict = DecisionVerdict::fromPayload([
            'degraded' => true,
            'filters' => [
                'mainstream_media' => 'skipped',
                'children_entertainment' => 'ok',
                'political' => 'failed: APIConnectionError: connection error',
                'fringe' => 'skipped',
            ],
        ]);

        $this->assertSame(
            ['political' => 'failed: APIConnectionError: connection error'],
            $verdict->failedFilters()
        );
        $this->assertSame(['mainstream_media', 'fringe'], $verdict->skippedFilters());
    }

    /**
     * 'not_implemented' is not a failure either.
     *
     * The deployed decision function writes
     * filters['already_watched'] = 'not_implemented' on its unconditional
     * success path -- every invocation, control and treated alike (32 of 32
     * archived payloads since 20260902-144403, 31 of them with degraded:false).
     * already_watched is always-on hygiene, never a configured treatment, so it
     * is absent from the function's own requested_treatments denominator and its
     * own gate ignores it: filter_failed is
     * any(str(status).startswith('failed')).
     *
     * Counting it made meta.verdict.failed_filters report one failed filter on
     * 100% of healthy lambdaselect jobs -- the always-on alarm that 'skipped' is
     * called out for one test above, wearing a different value. The set has to
     * match orchestrator/status.py's _FILTER_OK_STATES, which is the copy this
     * one is written against.
     *
     * @covers ::failedFilters
     * @covers ::skippedFilters
     */
    public function test_a_not_implemented_filter_is_neither_a_failure_nor_a_skip(): void
    {
        $verdict = DecisionVerdict::fromPayload([
            'degraded' => false,
            'treatment_lost' => false,
            'selection_mode' => 'random',
            'filters' => [
                'already_watched' => 'not_implemented',
                'mainstream_media' => 'skipped',
                'children_entertainment' => 'skipped',
                'similar' => 'skipped',
                'political' => 'skipped',
                'fringe' => 'skipped',
            ],
        ]);

        $this->assertSame([], $verdict->failedFilters(), 'the whole healthy control fleet would report a casualty');
        $this->assertNotContains('already_watched', $verdict->skippedFilters(), 'a stub is not "not requested"');
        $this->assertSame('ok', $verdict->label());
    }

    /**
     * ...and on the LEGACY path, where degradation has to be re-derived from the
     * filter states, the same value must not invent a degradation either.
     *
     * @covers ::fromPayload
     */
    public function test_not_implemented_does_not_degrade_a_legacy_payload(): void
    {
        $verdict = DecisionVerdict::fromPayload([
            'filters' => ['already_watched' => 'not_implemented', 'political' => 'skipped'],
            'selection_mode' => 'random',
        ]);

        $this->assertTrue($verdict->reported, 'filters alone is a report');
        $this->assertFalse($verdict->degraded);
        $this->assertFalse($verdict->treatmentLost);
    }

    /**
     * Absence of a report is UNKNOWN. It is not degradation -- reading it that
     * way turns every archived HAR into an incident -- and it is not health,
     * which would assert something nobody measured.
     *
     * @covers ::reportPresent
     */
    public function test_a_payload_with_no_report_is_unknown_not_clean(): void
    {
        $verdict = DecisionVerdict::fromPayload([
            'xpath' => '(//a)[1]',
            'present' => [],
            'procedure' => [],
            'selected' => [],
            'timestamps' => [],
            'warnings' => [],
        ]);

        $this->assertFalse($verdict->reported);
        $this->assertFalse($verdict->degraded);
        $this->assertFalse($verdict->treatmentLost);
        $this->assertSame('no report', $verdict->label());
    }

    /**
     * selection_mode by itself does not make a payload "reported": it is a
     * descriptor, and a payload carrying only a mode still cannot say whether
     * anything degraded.
     *
     * @covers ::reportPresent
     */
    public function test_a_selection_mode_alone_does_not_make_a_report(): void
    {
        $this->assertFalse(DecisionVerdict::fromPayload(['selection_mode' => 'random'])->reported);
        $this->assertTrue(DecisionVerdict::fromPayload(['filters' => []])->reported);
        $this->assertTrue(DecisionVerdict::fromPayload(['degraded' => false])->reported);
    }

    /**
     * A payload written after 'degraded' but before 'treatment_lost' recomputes
     * the same TWO terms -- degraded AND nothing requested applied -- never
     * selection_mode alone.
     *
     * @covers ::fromPayload
     */
    public function test_treatment_lost_falls_back_to_both_terms_never_one(): void
    {
        $lost = DecisionVerdict::fromPayload(['degraded' => true, 'selection_mode' => 'random']);
        $this->assertTrue($lost->treatmentLost);

        // Degraded, but the LLM still informed the pick: part of the treatment
        // happened, so the session is not void.
        $partial = DecisionVerdict::fromPayload(['degraded' => true, 'selection_mode' => 'llm']);
        $this->assertFalse($partial->treatmentLost);

        // Random and nothing wrong: the healthy control case again.
        $control = DecisionVerdict::fromPayload(['degraded' => false, 'selection_mode' => 'random']);
        $this->assertFalse($control->treatmentLost);
    }

    /**
     * treatment_lost implies degraded, so a payload carrying only the newer
     * field is still self-consistent.
     *
     * @covers ::fromPayload
     */
    public function test_treatment_lost_implies_degraded(): void
    {
        $verdict = DecisionVerdict::fromPayload(['treatment_lost' => true]);

        $this->assertTrue($verdict->degraded);
        $this->assertTrue($verdict->treatmentLost);
        $this->assertSame('treatment-lost', $verdict->label());
    }

    /**
     * The explicit flag wins over every inference. If the decision function
     * says it was fine, it was fine -- it computed the two-term gate at the
     * source precisely so no consumer has to.
     *
     * @covers ::fromPayload
     */
    public function test_the_explicit_flag_wins_over_the_fallbacks(): void
    {
        $verdict = DecisionVerdict::fromPayload([
            'degraded' => false,
            'warnings' => ['ID and title of present video not in the output'],
            'filters' => ['already_watched' => 'failed: boom'],
        ]);

        $this->assertFalse($verdict->degraded, 'an explicit degraded=false is the answer');
    }

    /**
     * With no explicit flag, the fallback uses evidence the decision function
     * itself produced: a failed filter, or a non-empty warnings list (which it
     * only writes when something it tried to do did not work).
     *
     * @covers ::fromPayload
     */
    public function test_the_fallback_reads_filters_and_warnings(): void
    {
        $this->assertTrue(DecisionVerdict::fromPayload(['filters' => ['political' => 'failed: boom']])->degraded);
        $this->assertTrue(DecisionVerdict::fromPayload(['filters' => [], 'warnings' => ['something']])->degraded);
        $this->assertFalse(DecisionVerdict::fromPayload(['filters' => ['political' => 'ok']])->degraded);
    }

    /**
     * A hard 4xx/5xx from the lambda server is a different thing again: the
     * call itself failed, so there is no decision to have an opinion about.
     *
     * @covers ::label
     */
    public function test_a_hard_error_outranks_everything(): void
    {
        $verdict = DecisionVerdict::fromPayload(['error' => 'connection refused', 'degraded' => true]);

        $this->assertSame('connection refused', $verdict->error);
        $this->assertSame('error', $verdict->label());
    }

    /**
     * Filters come back in the contract's order whatever order they arrived in,
     * and a key nobody has heard of is carried along at the end rather than
     * dropped.
     *
     * @covers ::fromPayload
     */
    public function test_filters_are_ordered_and_unknown_keys_survive(): void
    {
        $verdict = DecisionVerdict::fromPayload([
            'filters' => [
                'fringe' => 'skipped',
                'brand_new_filter' => 'ok',
                'already_watched' => 'ok',
            ],
        ]);

        $this->assertSame(['already_watched', 'fringe', 'brand_new_filter'], array_keys($verdict->filters));
    }

    /**
     * Only the members the column is defined to hold are kept, so the several-MB
     * 'procedure' block never reaches the database.
     *
     * @covers ::fromPayload
     */
    public function test_the_report_keeps_only_the_contract_members(): void
    {
        $verdict = DecisionVerdict::fromPayload([
            'xpath' => '(//a)[1]',
            'procedure' => ['a huge block of per-candidate scores'],
            'filters' => ['political' => 'ok'],
            'llm' => ['available' => true],
            'warnings' => ['w'],
            'applied' => ['political'],
            'requested' => ['political'],
        ]);

        $this->assertSame(
            ['filters', 'llm', 'warnings', 'applied', 'requested'],
            array_keys($verdict->report)
        );
    }
}
