<?php

namespace Tests\Feature\Modules\Job\Service;

use App\Modules\Job\Exception\DecisionPayloadException;
use App\Modules\Job\Model\Job;
use App\Modules\Job\Service\JobDecisionService;
use App\Modules\Queue\Model\Queue;
use App\Modules\Report\Model\Report;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesDecisionPayloads;
use Tests\TestCase;

/**
 * Reading a decision out of the object store.
 *
 * The centrepiece is test_it_parses_a_real_production_object: the fixture's
 * output entry is the verbatim one MinIO holds for job 34226, read off the box
 * on 2026-09-07. Three layers have to be got right to reach the verdict -- a
 * zip, an entry whose name has to be matched rather than guessed, and an
 * envelope whose statusCode is 200 whatever the decision did -- and every one
 * of them is a place a reader can go wrong while still returning something that
 * looks plausible.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Job\Service\JobDecisionService
 */
class JobDecisionServiceTest extends TestCase
{
    use MakesDecisionPayloads;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Report::S3DISK);
    }

    /**
     * @covers ::payloadForReport
     */
    public function test_it_parses_a_real_production_object(): void
    {
        $job = $this->lambdaJob();
        // Real bytes, filed under the job under test: an object key names the
        // job it belongs to, and Report::storageKey() will not read one that
        // names a different job. The fixture is job 34226's object on ex63.
        $key = 'reports/lambda-jobdata-lambdaselectjob-youtube_video_select-'.$job->id;

        Storage::disk(Report::S3DISK)->put(
            $key,
            (string) file_get_contents(base_path('tests/Fixtures/decision/lambda-jobdata-lambdaselectjob-youtube_video_select-34226.zip'))
        );

        $report = Report::factory()->create([
            'job_id' => $job->id,
            'user_id' => $job->user_id,
            'filename' => basename($key),
            'path' => Report::S3PREFIX.$key,
            'mimetype' => Report::S3MIMETYPE,
            'filecontent' => null,
        ]);

        $payload = $this->service()->payloadForReport($report);
        $verdict = $payload->verdict();

        // The envelope says the CALL was fine, which is all it ever says.
        $this->assertSame(200, $payload->invocation['statusCode']);
        $this->assertStringContainsString('lambda_output', $payload->entry);

        // The decision itself: a control crawler, picking at random, healthy.
        $this->assertTrue($verdict->reported);
        $this->assertFalse($verdict->degraded);
        $this->assertFalse($verdict->treatmentLost);
        $this->assertSame('random', $verdict->selectionMode);
        $this->assertSame('skipped', $verdict->filters['mainstream_media']);
        $this->assertSame('ok', $verdict->filters['children_entertainment']);

        // Five filters were never requested. Counting them as casualties is the
        // mistake this contract exists to prevent.
        $this->assertContains('mainstream_media', $verdict->skippedFilters());
        $this->assertContains('political', $verdict->skippedFilters());

        // The real payload carries a warning and an explicit degraded=false.
        // The explicit flag wins: the function looked and said it was fine.
        $this->assertNotEmpty($verdict->warnings);
        $this->assertFalse($verdict->degraded);
    }

    /**
     * The verdict is copied straight from the columns' point of view: only what
     * the contract defines lands, and the multi-MB procedure block does not.
     *
     * @covers ::columnsFor
     */
    public function test_columns_carry_only_the_contract_members(): void
    {
        $job = $this->lambdaJob();
        $this->storeDecisionFor($job, $this->reportedPayload([
            'degraded' => true,
            'treatment_lost' => false,
            'selection_mode' => 'llm',
            'procedure' => ['pages' => 'and pages of scores'],
        ]));

        $columns = $this->service()->columnsFor($this->service()->payloadFor($job)->verdict());

        $this->assertTrue($columns['decision_degraded']);
        $this->assertFalse($columns['decision_treatment_lost']);
        $this->assertSame('llm', $columns['decision_selection_mode']);
        $this->assertArrayNotHasKey('procedure', $columns['decision_report']);
    }

    /**
     * The rule that decides whether the jobs table ends up telling the truth: a
     * payload with no degradation report writes NOTHING. Writing false would
     * assert that a treatment applied when nobody measured whether it did.
     *
     * @covers ::columnsFor
     */
    public function test_a_legacy_payload_yields_no_columns_at_all(): void
    {
        $job = $this->lambdaJob();
        $this->storeDecisionFor($job, $this->legacyPayload());

        $this->assertSame([], $this->service()->columnsFor($this->service()->payloadFor($job)->verdict()));
    }

    /**
     * A job with no decision object gets null, not an exception: most jobs
     * never call the decision function at all.
     *
     * @covers ::payloadFor
     */
    public function test_a_job_with_no_jobdata_report_yields_null(): void
    {
        $this->assertNull($this->service()->payloadFor($this->lambdaJob()));
    }

    /**
     * ...while a recorded object that cannot be read raises, because "nothing
     * was recorded" and "we cannot fetch what was recorded" are different
     * answers and only the second one is missing evidence.
     *
     * @covers ::payloadForReport
     */
    public function test_a_recorded_object_that_is_gone_raises(): void
    {
        $job = $this->lambdaJob();
        $report = $this->storeMissingDecisionFor($job);

        $this->expectException(DecisionPayloadException::class);
        $this->service()->payloadForReport($report);
    }

    /**
     * A bare JSON body -- no zip -- is read too. The writer has changed shape
     * before, and failing to read a payload that is right there would be worse
     * than a branch that never fires.
     *
     * @covers ::payloadForReport
     */
    public function test_a_bare_json_object_is_read(): void
    {
        $job = $this->lambdaJob();
        $key = 'reports/lambda-jobdata-lambdaselectjob-youtube_video_select-'.$job->id;

        Storage::disk(Report::S3DISK)->put($key, (string) json_encode([
            'statusCode' => 200,
            'payload' => json_encode(['degraded' => true, 'selection_mode' => 'random']),
        ]));

        $report = Report::factory()->create([
            'job_id' => $job->id,
            'user_id' => $job->user_id,
            'filename' => basename($key),
            'path' => Report::S3PREFIX.$key,
        ]);

        $verdict = $this->service()->payloadForReport($report)->verdict();

        $this->assertTrue($verdict->degraded);
        $this->assertTrue($verdict->treatmentLost, 'degraded + random, both terms, is a lost treatment');
    }

    /**
     * An already-unwrapped body parses as well, so an object written by
     * something other than the runner still reads.
     *
     * @covers ::unwrapInvocation
     */
    public function test_an_envelope_free_body_is_returned_unchanged(): void
    {
        $service = $this->service();

        $this->assertSame(
            ['degraded' => true],
            $service->unwrapInvocation(['degraded' => true])
        );
        $this->assertSame(
            ['degraded' => true],
            $service->unwrapInvocation(['statusCode' => 200, 'payload' => ['degraded' => true]])
        );
        // An unparseable inner payload leaves the envelope, which then reads as
        // "no report" rather than as a clean decision.
        $this->assertSame(
            ['statusCode' => 200, 'payload' => 'not json'],
            $service->unwrapInvocation(['statusCode' => 200, 'payload' => 'not json'])
        );
    }

    private function service(): JobDecisionService
    {
        return app(JobDecisionService::class);
    }

    private function lambdaJob(): Job
    {
        return Job::factory()->create([
            'queue_id' => $this->queue->id,
            'user_id' => $this->user->id,
            'status_code' => Queue::STATUS_SUCCESS,
        ]);
    }
}
