<?php

namespace Tests\Feature\Modules\Job\Http\Controller\Api\v1;

use App\Models\User;
use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use App\Modules\Report\Model\Report;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\Concerns\MakesDecisionPayloads;
use Tests\TestCase;

/**
 * GET /api/v1/jobs/{id}/decision.
 *
 * The four decision_* columns only ever describe jobs run after the runner
 * deploy. The 54,000 lambda-jobdata objects in MinIO go back to the start, and
 * until this endpoint existed nothing in the API could read one -- the reports
 * row is a pointer (mimetype primemover/s3, filecontent NULL) and the bytes are
 * in the store. This is what makes the historical verdicts readable without a
 * backfill.
 *
 * Storage::fake throughout; no test here touches the real MinIO.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Job\Http\Controller\Api\v1\JobController
 */
class JobDecisionEndpointTest extends TestCase
{
    use MakesDecisionPayloads;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Report::S3DISK);
    }

    /**
     * @covers ::decision
     */
    public function test_it_serves_the_payload_out_of_the_object_store(): void
    {
        $job = $this->lambdaJob();
        $report = $this->storeDecisionFor($job, $this->reportedPayload([
            'degraded' => true,
            'treatment_lost' => true,
            'selection_mode' => 'random',
            'filters' => ['political' => 'failed: APIConnectionError: boom', 'fringe' => 'skipped'],
        ]));

        $body = $this->decisionFor($job)->assertOk()->json();

        $this->assertTrue($body['data']['degraded']);
        $this->assertTrue($body['data']['treatment_lost']);
        $this->assertSame('(//ytd-compact-video-renderer)[3]', $body['data']['xpath']);

        $this->assertSame($job->id, $body['meta']['job_id']);
        $this->assertSame($report->id, $body['meta']['report_id']);
        $this->assertStringContainsString('lambda_output_jobdata', $body['meta']['entry']);
    }

    /**
     * The envelope describes the CALL, which is 200 even when every requested
     * filter failed. It is reported beside the decision, never as the decision:
     * reading statusCode as the verdict is exactly the silent success the
     * degradation report exists to prevent.
     *
     * @covers ::decision
     */
    public function test_the_runner_envelope_is_reported_separately_from_the_decision(): void
    {
        $job = $this->lambdaJob();
        $this->storeDecisionFor($job, $this->reportedPayload(['degraded' => true, 'treatment_lost' => true]));

        $meta = $this->decisionFor($job)->assertOk()->json('meta');

        $this->assertSame(200, $meta['invocation']['statusCode']);
        $this->assertTrue($meta['invocation']['success']);
        // ...and the verdict computed off the decision itself says otherwise.
        $this->assertTrue($meta['verdict']['treatment_lost']);
        $this->assertSame('treatment-lost', $meta['verdict']['label']);
    }

    /**
     * The endpoint applies the contract's rules once so no client reimplements
     * them, and 'skipped' filters never appear among the casualties.
     *
     * @covers ::decision
     */
    public function test_the_verdict_is_computed_for_the_client(): void
    {
        $job = $this->lambdaJob();
        $this->storeDecisionFor($job, $this->reportedPayload([
            'degraded' => true,
            'treatment_lost' => false,
            'selection_mode' => 'llm',
            'filters' => [
                'political' => 'failed: RateLimitError: slow down',
                'fringe' => 'skipped',
                'similar' => 'ok',
            ],
        ]));

        $verdict = $this->decisionFor($job)->assertOk()->json('meta.verdict');

        $this->assertTrue($verdict['reported']);
        $this->assertTrue($verdict['degraded']);
        $this->assertFalse($verdict['treatment_lost']);
        $this->assertSame(['political' => 'failed: RateLimitError: slow down'], $verdict['failed_filters']);
        $this->assertSame(['fringe'], $verdict['skipped_filters']);
        $this->assertSame('degraded', $verdict['label']);
    }

    /**
     * A payload from before the degradation report is served as it was written
     * and reported as UNKNOWN -- never as a clean decision.
     *
     * @covers ::decision
     */
    public function test_a_legacy_payload_is_served_and_reported_unknown(): void
    {
        $job = $this->lambdaJob();
        $this->storeDecisionFor($job, $this->legacyPayload());

        $body = $this->decisionFor($job)->assertOk()->json();

        $this->assertArrayNotHasKey('degraded', $body['data']);
        $this->assertFalse($body['meta']['verdict']['reported']);
        $this->assertSame('no report', $body['meta']['verdict']['label']);
    }

    /**
     * The ordinary answer for a visit, a wait or a type job: no decision was
     * ever recorded. It says nothing was recorded, not that nothing went wrong.
     *
     * @covers ::decision
     */
    public function test_a_job_with_no_decision_report_is_404(): void
    {
        $this->withExceptionHandling();

        $this->decisionFor($this->lambdaJob())->assertNotFound();
    }

    /**
     * A job's OTHER objects are not decisions. The HAR (static-jobdata) is not
     * a payload and must not be mistaken for one.
     *
     * @covers ::decision
     */
    public function test_a_har_report_is_not_mistaken_for_a_decision(): void
    {
        $this->withExceptionHandling();
        $job = $this->lambdaJob();

        Report::factory()->create([
            'job_id' => $job->id,
            'user_id' => $job->user_id,
            'filename' => 'static-jobdata-lambdaselectjob-youtube_video_select-'.$job->id,
            'path' => Report::S3PREFIX.'reports/static-jobdata-lambdaselectjob-youtube_video_select-'.$job->id,
        ]);

        $this->decisionFor($job)->assertNotFound();
    }

    /**
     * Deliberately NOT a 404.
     *
     * The pointer row exists, so we know a decision was made; the store simply
     * would not give it up. A 404 would read as "this job made no decision",
     * which is the one thing we know is false. An unreachable store is missing
     * evidence about a job, never evidence that the job was fine.
     *
     * @covers ::decision
     */
    public function test_a_recorded_but_unreadable_payload_is_502_not_404(): void
    {
        $this->withExceptionHandling();
        $job = $this->lambdaJob();
        $this->storeMissingDecisionFor($job);

        $this->decisionFor($job)->assertStatus(502);
    }

    /**
     * A zip that holds only the multi-MB input dump and no output entry is the
     * same class of answer: something is there and it is not readable as a
     * decision.
     *
     * @covers ::decision
     */
    public function test_a_zip_with_no_output_entry_is_502(): void
    {
        $this->withExceptionHandling();
        $job = $this->lambdaJob();
        $this->storeDecisionFor($job, null);

        $this->decisionFor($job)->assertStatus(502);
    }

    /**
     * The newest object wins: a retried job writes more than one, and the last
     * decision is the one that produced the data.
     *
     * @covers ::decision
     */
    public function test_the_newest_recorded_decision_wins(): void
    {
        $job = $this->lambdaJob();
        $this->storeDecisionFor($job, $this->reportedPayload(['selection_mode' => 'random']));
        $second = $this->storeDecisionFor($job, $this->reportedPayload(['selection_mode' => 'llm']));

        $body = $this->decisionFor($job)->assertOk()->json();

        $this->assertSame($second->id, $body['meta']['report_id']);
        $this->assertSame('llm', $body['data']['selection_mode']);
    }

    /**
     * Same authorization as every other job endpoint: the job view policy,
     * which walks queue -> crawler -> experiment. A researcher must not be able
     * to read another study's decisions by guessing a job id.
     *
     * @covers ::decision
     */
    public function test_another_researcher_cannot_read_the_decision(): void
    {
        $this->withExceptionHandling();

        $job = $this->lambdaJob();
        $this->storeDecisionFor($job, $this->reportedPayload());

        $stranger = User::factory()->create();
        config([
            'primemover.isolation.enabled' => true,
            'primemover.isolation.service_account_ids' => [],
        ]);

        Passport::actingAs($stranger);
        $this->getJson('/api/v1/jobs/'.$job->id.'/decision')->assertForbidden();

        // ...and the owner still can.
        Passport::actingAs($this->user);
        $this->getJson('/api/v1/jobs/'.$job->id.'/decision')->assertOk();
    }

    /**
     * @covers ::decision
     */
    public function test_an_unauthenticated_caller_is_refused_before_anything_is_read(): void
    {
        $this->withExceptionHandling();

        $job = $this->lambdaJob();
        $this->storeDecisionFor($job, $this->reportedPayload());

        $this->getJson('/api/v1/jobs/'.$job->id.'/decision')->assertUnauthorized();
    }

    private function lambdaJob(): Job
    {
        return Job::factory()->create([
            'queue_id' => $this->queue->id,
            'user_id' => $this->user->id,
            'status_code' => Queue::STATUS_SUCCESS,
        ]);
    }

    private function decisionFor(Job $job): \Illuminate\Testing\TestResponse
    {
        Passport::actingAs($this->user);

        return $this->getJson('/api/v1/jobs/'.$job->id.'/decision');
    }
}
