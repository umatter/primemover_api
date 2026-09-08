<?php

namespace Tests\Feature\Modules\Job\Http\Controller\Api\v1;

use App\Modules\Job\Http\Request\RunnerJobUpdateRequest;
use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * The decision function's verdict, arriving on the runner's status PUT.
 *
 * Two things are being protected here and they pull in opposite directions:
 * the verdict must land (it is the only reason the overhaul exists), and it
 * must never be able to cost the job its terminal state. The runner swallows a
 * 422 -- JobService::updateJobState logs and moves on -- so a rejected update
 * leaves the row at 'running' for ever, taking the run's rollup with it. Hence:
 * coerce what can be coerced, drop what cannot, reject nothing.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Job\Http\Request\RunnerJobUpdateRequest
 */
class RunnerJobDecisionTest extends TestCase
{
    /**
     * @covers ::rules
     */
    public function test_the_runner_put_stores_all_four_decision_fields(): void
    {
        $job = $this->runningJob();

        $this->updateJob($job, [
            'status_code' => Queue::STATUS_WARNING,
            'status_message' => 'minor error encountered - treatment lost',
            'decision_degraded' => true,
            'decision_treatment_lost' => true,
            'decision_selection_mode' => 'random',
            'decision_report' => [
                'filters' => ['political' => 'failed: APIConnectionError: connection error', 'fringe' => 'skipped'],
                'llm' => ['available' => false, 'error_type' => 'APIConnectionError', 'error' => 'connection error'],
                'warnings' => ['political filter not applied'],
            ],
        ])->assertOk();

        $fresh = $job->fresh();

        $this->assertTrue($fresh->decision_degraded);
        $this->assertTrue($fresh->decision_treatment_lost);
        $this->assertSame('random', $fresh->decision_selection_mode);
        $this->assertSame('failed: APIConnectionError: connection error', $fresh->decision_report['filters']['political']);
        $this->assertSame('APIConnectionError', $fresh->decision_report['llm']['error_type']);
        $this->assertSame(['political filter not applied'], $fresh->decision_report['warnings']);
    }

    /**
     * A control crawler's healthy decision. selection_mode 'random' with
     * degraded false is the correct, expected answer for 82% of the fleet and
     * must be stored exactly as sent -- it is a description, not a verdict.
     *
     * @covers ::rules
     */
    public function test_a_clean_control_decision_stores_false_not_null(): void
    {
        $job = $this->runningJob();

        $this->updateJob($job, [
            'status_code' => Queue::STATUS_SUCCESS,
            'decision_degraded' => false,
            'decision_treatment_lost' => false,
            'decision_selection_mode' => 'random',
        ])->assertOk();

        $fresh = $job->fresh();

        // false and null are different answers: false is "we looked and nothing
        // degraded", null is "we cannot tell".
        $this->assertFalse($fresh->decision_degraded);
        $this->assertNotNull($fresh->decision_degraded);
        $this->assertFalse($fresh->decision_treatment_lost);
        $this->assertSame('random', $fresh->decision_selection_mode);
    }

    /**
     * The mixed-version case, and the reason every column is nullable: a runner
     * that predates the change sends no decision fields at all, and the row must
     * say "unknown" rather than "clean".
     *
     * @covers ::rules
     */
    public function test_omitting_the_fields_leaves_them_null(): void
    {
        $job = $this->runningJob();

        $this->updateJob($job, [
            'status_code' => Queue::STATUS_SUCCESS,
            'status_message' => 'successfully finished job',
        ])->assertOk();

        $fresh = $job->fresh();

        $this->assertNull($fresh->decision_degraded);
        $this->assertNull($fresh->decision_treatment_lost);
        $this->assertNull($fresh->decision_selection_mode);
        $this->assertNull($fresh->decision_report);
    }

    /**
     * A later status PUT that carries no verdict must not erase the verdict an
     * earlier one recorded. The runner reports a job more than once.
     *
     * @covers ::rules
     */
    public function test_a_later_put_without_a_verdict_does_not_erase_one(): void
    {
        $job = $this->runningJob();

        $this->updateJob($job, [
            'status_code' => Queue::STATUS_WARNING,
            'decision_degraded' => true,
            'decision_treatment_lost' => true,
        ])->assertOk();

        $this->updateJob($job->fresh(), [
            'status_code' => Queue::STATUS_WARNING,
            'status_message' => 're-reported after a restart',
        ])->assertOk();

        $this->assertTrue($job->fresh()->decision_treatment_lost);
    }

    /**
     * Deliberately no in: whitelist. A mode the API has not heard of is stored
     * as sent: the column describes how the video was picked, nothing may
     * branch on it alone, and an operator needs to see the unfamiliar value in
     * the row rather than have it rewritten or refused.
     *
     * @covers ::rules
     */
    public function test_an_unknown_selection_mode_is_stored_not_rejected(): void
    {
        $job = $this->runningJob();

        $this->updateJob($job, [
            'status_code' => Queue::STATUS_SUCCESS,
            'decision_selection_mode' => 'quantum-annealing',
        ])->assertOk();

        $this->assertSame('quantum-annealing', $job->fresh()->decision_selection_mode);
    }

    /**
     * varchar(32). Over-long means truncated, never 422 -- the job's terminal
     * state is riding on the same request.
     *
     * @covers ::rules
     */
    public function test_an_over_long_selection_mode_is_truncated_not_rejected(): void
    {
        $job = $this->runningJob();

        $this->updateJob($job, [
            'status_code' => Queue::STATUS_SUCCESS,
            'decision_selection_mode' => str_repeat('m', 200),
        ])->assertOk();

        $stored = (string) $job->fresh()->decision_selection_mode;

        $this->assertSame(RunnerJobUpdateRequest::DECISION_SELECTION_MODE_MAX, mb_strlen($stored, '8bit'));
        $this->assertSame(Queue::STATUS_SUCCESS, $job->fresh()->status_code);
    }

    /**
     * A verdict that cannot be read is dropped, so the column stays NULL --
     * unknown -- and the STATUS still lands. Rejecting the request would be the
     * worst of both: no verdict and no status either.
     *
     * @covers ::rules
     */
    public function test_an_uncoercible_verdict_is_dropped_and_the_status_still_lands(): void
    {
        $job = $this->runningJob();

        $this->updateJob($job, [
            'status_code' => Queue::STATUS_FAILURE,
            'decision_degraded' => ['not', 'a', 'boolean'],
            'decision_treatment_lost' => 'maybe',
            'decision_selection_mode' => ['llm'],
            'decision_report' => 'this is not json',
        ])->assertOk();

        $fresh = $job->fresh();

        $this->assertSame(Queue::STATUS_FAILURE, $fresh->status_code);
        $this->assertNull($fresh->decision_degraded);
        $this->assertNull($fresh->decision_treatment_lost);
        $this->assertNull($fresh->decision_selection_mode);
        $this->assertNull($fresh->decision_report);
    }

    /**
     * Booleans off the wire arrive in whatever shape the sender's JSON library
     * chose. All of these mean true.
     *
     * @covers ::rules
     */
    public function test_string_and_numeric_booleans_are_coerced(): void
    {
        foreach ([['true', 'false'], ['1', '0'], [1, 0]] as [$yes, $no]) {
            $job = $this->runningJob();

            $this->updateJob($job, [
                'status_code' => Queue::STATUS_SUCCESS,
                'decision_degraded' => $yes,
                'decision_treatment_lost' => $no,
            ])->assertOk();

            $fresh = $job->fresh();
            $this->assertTrue($fresh->decision_degraded, 'expected '.var_export($yes, true).' to mean true');
            $this->assertFalse($fresh->decision_treatment_lost, 'expected '.var_export($no, true).' to mean false');
        }
    }

    /**
     * A client that encodes the nested object once too often is not a reason to
     * lose a verdict.
     *
     * @covers ::rules
     */
    public function test_a_report_sent_as_a_json_string_is_decoded(): void
    {
        $job = $this->runningJob();

        $this->updateJob($job, [
            'status_code' => Queue::STATUS_WARNING,
            'decision_report' => json_encode(['filters' => ['political' => 'ok']]),
        ])->assertOk();

        $this->assertSame(['filters' => ['political' => 'ok']], $job->fresh()->decision_report);
    }

    /**
     * An enormous report is shrunk member by member, worst first, and marked --
     * never truncated as a byte string (half a JSON document is not a JSON
     * document) and never rejected.
     *
     * @covers ::rules
     */
    public function test_an_over_long_report_is_shrunk_and_marked_not_rejected(): void
    {
        $job = $this->runningJob();

        $this->updateJob($job, [
            'status_code' => Queue::STATUS_WARNING,
            'decision_report' => [
                'filters' => ['political' => 'failed: boom'],
                'llm' => ['available' => false],
                'warnings' => [str_repeat('w', RunnerJobUpdateRequest::DECISION_REPORT_MAX + 10)],
            ],
        ])->assertOk();

        $stored = $job->fresh()->decision_report;

        $this->assertIsArray($stored);
        $this->assertTrue($stored['_truncated']);
        $this->assertArrayNotHasKey('warnings', $stored, 'warnings is the unbounded member and goes first');
        // ...and the part that names WHICH filter failed survives, because that
        // is the last thing worth losing.
        $this->assertSame('failed: boom', $stored['filters']['political']);
        $this->assertLessThanOrEqual(
            RunnerJobUpdateRequest::DECISION_REPORT_MAX,
            mb_strlen((string) json_encode($stored), '8bit')
        );
    }

    /**
     * There are no API Resource classes in this codebase -- models serialise
     * whole -- but "it will just work" is exactly the assumption that left the
     * GUI unable to see the rollups for a month.
     *
     * @covers \App\Modules\Job\Model\Job
     */
    public function test_the_fields_serialise_on_the_list_and_the_detail_endpoint(): void
    {
        $job = $this->runningJob();

        $this->updateJob($job, [
            'status_code' => Queue::STATUS_WARNING,
            'decision_degraded' => true,
            'decision_treatment_lost' => false,
            'decision_selection_mode' => 'llm',
            'decision_report' => ['filters' => ['similar' => 'ok']],
        ])->assertOk();

        Passport::actingAs($this->user);

        $detail = $this->getJson('/api/v1/jobs/'.$job->id)->assertOk()->json('data');

        $this->assertTrue($detail['decision_degraded']);
        $this->assertFalse($detail['decision_treatment_lost']);
        $this->assertSame('llm', $detail['decision_selection_mode']);
        $this->assertSame(['filters' => ['similar' => 'ok']], $detail['decision_report']);

        $listed = collect($this->getJson('/api/v1/jobs?queue='.$this->queue->id)->assertOk()->json('data'))
            ->firstWhere('id', $job->id);

        $this->assertNotNull($listed, 'the job under test was not on the /jobs page');
        $this->assertTrue($listed['decision_degraded']);
        $this->assertSame('llm', $listed['decision_selection_mode']);

        // A job nobody reported a verdict for serialises the three-state column
        // as null -- present and unknown, not absent and not false.
        $other = Job::factory()->create(['queue_id' => $this->queue->id, 'status_code' => Queue::STATUS_SUCCESS]);
        $otherRow = $this->getJson('/api/v1/jobs/'.$other->id)->assertOk()->json('data');

        $this->assertArrayHasKey('decision_treatment_lost', $otherRow);
        $this->assertNull($otherRow['decision_treatment_lost']);
    }

    /**
     * An empty verdict flag is UNKNOWN, never a measured false.
     *
     * filter_var() reads "" as FALSE -- it sits in FILTER_VALIDATE_BOOL's false
     * list beside "0"/"off"/"no" -- so before this was fixed a sender that said
     * nothing at all had "the treatment applied" written into the one table
     * every analysis joins on. Every downstream reader then counts that job in
     * the not-degraded direction and decisions:backfill will never revisit it
     * (its whereNull guard no longer matches), so the object in MinIO that holds
     * the real answer is never read for it again.
     *
     * The global TrimStrings/ConvertEmptyStringsToNull middleware turns a bare
     * "" into null before this class ever sees it, which is why the value is
     * asserted at the FormRequest instead of over HTTP: the guard has to hold
     * for the layer that owns it, not because of a middleware two floors up
     * that a future route could be registered without.
     *
     * @covers ::rules
     */
    public function test_an_empty_verdict_flag_is_unknown_not_false(): void
    {
        foreach (['', '   '] as $blank) {
            $request = RunnerJobUpdateRequest::create('/api/v1/runner/jobs/1', 'PUT', [
                'status_code' => Queue::STATUS_SUCCESS,
                'decision_degraded' => $blank,
                'decision_treatment_lost' => $blank,
            ]);
            $request->setContainer($this->app)->setRedirector($this->app->make(\Illuminate\Routing\Redirector::class));
            $request->validateResolved();

            $validated = $request->validated();

            $this->assertArrayNotHasKey('decision_degraded', $validated, 'an empty flag must not be stored as a measured false');
            $this->assertArrayNotHasKey('decision_treatment_lost', $validated);
            $this->assertSame(Queue::STATUS_SUCCESS, $validated['status_code']);
        }

        // ...and a real false is still a real false.
        $request = RunnerJobUpdateRequest::create('/api/v1/runner/jobs/1', 'PUT', [
            'status_code' => Queue::STATUS_SUCCESS,
            'decision_degraded' => 'false',
            'decision_treatment_lost' => '0',
        ]);
        $request->setContainer($this->app)->setRedirector($this->app->make(\Illuminate\Routing\Redirector::class));
        $request->validateResolved();

        $this->assertFalse($request->validated()['decision_degraded']);
        $this->assertFalse($request->validated()['decision_treatment_lost']);
    }

    /**
     * A report MySQL would refuse is shrunk, not sent to the database.
     *
     * MySQL's JSON type rejects any document nested deeper than 100 levels
     * (ER_JSON_DOCUMENT_TOO_DEEP, 3157); PHP's json_encode() encodes to 512. A
     * small-but-deep report therefore passed the byte guard, reached
     * $job->update(), and made the whole status PUT a 500 -- costing the job its
     * terminal state, which is the one outcome this class exists to prevent.
     * SQLite stores json() as TEXT and accepts any depth, so nothing but the
     * shape of the stored value can show this here.
     *
     * @covers ::rules
     */
    public function test_a_report_deeper_than_mysql_allows_is_shrunk_and_the_status_lands(): void
    {
        $job = $this->runningJob();

        $deep = ['bottom' => true];

        for ($level = 0; $level < 150; $level++) {
            $deep = ['a' => $deep];
        }

        $this->updateJob($job, [
            'status_code' => Queue::STATUS_SUCCESS,
            'decision_report' => ['filters' => ['similar' => 'ok'], 'warnings' => $deep],
        ])->assertOk();

        $fresh = $job->fresh();

        $this->assertSame(Queue::STATUS_SUCCESS, $fresh->status_code, 'the status must land whatever the report was');
        $this->assertLessThan(
            100,
            $this->depthOf((array) $fresh->decision_report),
            'a report deeper than MySQL allows was stored verbatim and would have failed the write'
        );
        $this->assertTrue($fresh->decision_report['_truncated'] ?? false, 'a shortened report must say so');
    }

    /**
     * Only a runner may write the verdict.
     *
     * PUT /v1/runner/jobs/{job} has no authorization of its own, and
     * decision_treatment_lost is not another status field: it is what
     * /experiments/{id}/health reports as treatment_lost_jobs and what every
     * alarm in this system reads. A NULL verdict flipped to a recorded false is
     * the transition the contract forbids, and afterwards every alarm reads a
     * truthful-looking 0.
     *
     * Same predicate as can:administer-platform, so it is inert while isolation
     * is off and for the service accounts the runner authenticates as.
     *
     * @covers ::rules
     */
    public function test_a_researchers_token_cannot_write_a_verdict(): void
    {
        $job = $this->runningJob();

        config([
            'primemover.isolation.enabled' => true,
            'primemover.isolation.service_account_ids' => [],
        ]);

        $this->updateJob($job, [
            'status_code' => Queue::STATUS_SUCCESS,
            'decision_degraded' => false,
            'decision_treatment_lost' => false,
            'decision_selection_mode' => 'random',
            'decision_report' => ['filters' => ['similar' => 'ok']],
        ])->assertOk();

        $fresh = $job->fresh();

        // The status still lands -- dropping is never allowed to cost the job
        // its terminal state.
        $this->assertSame(Queue::STATUS_SUCCESS, $fresh->status_code);

        // ...and the verdict stays unknown rather than becoming a measured
        // "nothing was lost" that nobody measured.
        $this->assertNull($fresh->decision_degraded);
        $this->assertNull($fresh->decision_treatment_lost);
        $this->assertNull($fresh->decision_selection_mode);
        $this->assertNull($fresh->decision_report);

        // The runner itself is a service account and is unaffected.
        config(['primemover.isolation.service_account_ids' => [$this->user->id]]);

        $this->updateJob($job, [
            'status_code' => Queue::STATUS_SUCCESS,
            'decision_degraded' => false,
            'decision_treatment_lost' => false,
        ])->assertOk();

        $this->assertFalse($job->fresh()->decision_degraded);
    }

    /**
     * @param array<mixed> $value
     */
    private function depthOf(array $value): int
    {
        $deepest = 1;

        foreach ($value as $child) {
            if (is_array($child)) {
                $deepest = max($deepest, 1 + $this->depthOf($child));
            }
        }

        return $deepest;
    }

    private function runningJob(): Job
    {
        return Job::factory()->create([
            'queue_id' => $this->queue->id,
            'user_id' => $this->user->id,
            'status_code' => Queue::STATUS_RUNNING,
            'status_message' => 'job started',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function updateJob(Job $job, array $payload): \Illuminate\Testing\TestResponse
    {
        Passport::actingAs($this->user);

        return $this->putJson('/api/v1/runner/jobs/'.$job->id, $payload);
    }
}
