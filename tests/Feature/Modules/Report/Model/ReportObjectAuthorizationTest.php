<?php

namespace Tests\Feature\Modules\Report\Model;

use App\Models\User;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use App\Modules\Report\Model\Report;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\Concerns\MakesDecisionPayloads;
use Tests\TestCase;

/**
 * The object served has to be the object the row was authorised for.
 *
 * GET /v1/file/{report} and GET /v1/jobs/{id}/decision authorise the reports
 * ROW -- through job -> queue -> crawler -> experiment -- and then used to fetch
 * whatever key reports.path happened to name. Those are two different questions.
 * POST /v1/reports lets an authenticated caller choose job_id AND path, Report
 * is $guarded = [], and ReportStoreRequest had no format rule on path at all, so
 * a row could be filed under a job the caller owns while pointing at another
 * researcher's HAR or decision payload. The policy passed; the bytes were
 * somebody else's.
 *
 * Both ends are covered here, and the read end matters more: the ~70,400 rows
 * already in the table were written before any rule existed, so a reader may not
 * assume its input was ever validated.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Report\Model\Report
 */
class ReportObjectAuthorizationTest extends TestCase
{
    use MakesDecisionPayloads;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Report::S3DISK);
    }

    /**
     * The attack the finding describes, end to end.
     *
     * @covers ::storageKey
     */
    public function test_a_row_pointing_at_another_researchers_object_serves_nothing(): void
    {
        $this->withExceptionHandling();

        $victimJob = $this->otherResearchersJob();
        $victimKey = 'reports/static-jobdata-visitjob-youtube-'.$victimJob->id;
        Storage::disk(Report::S3DISK)->put($victimKey, 'the-other-researchers-har');

        $crafted = Report::factory()->create([
            'job_id' => $this->ownJob()->id,
            'user_id' => $this->user->id,
            'filename' => basename($victimKey),
            'mimetype' => Report::S3MIMETYPE,
            'filecontent' => null,
            'path' => Report::S3PREFIX.$victimKey,
        ]);

        Passport::actingAs($this->user);
        $response = $this->get('/api/v1/file/'.$crafted->id);

        $response->assertNotFound();
        $response->assertDontSee('the-other-researchers-har');
    }

    /**
     * The same crafted row, through the decision endpoint.
     *
     * reportFor() takes the NEWEST lambda-jobdata row, so a crafted row also
     * shadows the job's real payload. It must be passed over, not returned and
     * then refused.
     *
     * @covers ::storageKey
     */
    public function test_a_crafted_row_neither_leaks_nor_shadows_the_real_decision(): void
    {
        $this->withExceptionHandling();

        $victimJob = $this->otherResearchersJob();
        $this->storeDecisionFor($victimJob, $this->reportedPayload([
            'degraded' => true,
            'treatment_lost' => true,
            'selection_mode' => 'random',
        ]));

        $own = $this->ownJob();
        $this->storeDecisionFor($own, $this->reportedPayload(['degraded' => false]));

        $victimKey = 'reports/lambda-jobdata-lambdaselectjob-youtube_video_select-'.$victimJob->id;

        // Newer id than the honest row, so it wins orderByDesc('id').
        Report::factory()->create([
            'job_id' => $own->id,
            'user_id' => $this->user->id,
            'filename' => basename($victimKey),
            'mimetype' => Report::S3MIMETYPE,
            'filecontent' => null,
            'path' => Report::S3PREFIX.$victimKey,
        ]);

        Passport::actingAs($this->user);
        $body = $this->getJson('/api/v1/jobs/'.$own->id.'/decision')->assertOk()->json();

        // Its own verdict, not the other job's.
        $this->assertFalse($body['data']['degraded']);
        $this->assertStringEndsWith((string) $own->id, $body['meta']['key']);
    }

    /**
     * Paths that leave the runner's object store, whatever is stored.
     *
     * @covers ::storageKeyFor
     *
     * @dataProvider refusedPaths
     */
    public function test_a_path_outside_the_runners_object_store_is_refused(string $path): void
    {
        $this->assertNull(Report::storageKeyFor($path, 4242), $path.' should not be readable');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function refusedPaths(): array
    {
        return [
            'another bucket' => ['s3://primemover-secrets/reports/static-jobdata-visitjob-x-4242'],
            'an http url' => ['https://siaw.primemover.example/api/v1/file/4242'],
            'an absolute filesystem path' => ['/etc/passwd-4242'],
            'a traversal out of the prefix' => ['s3://primemoverrunner/reports/../../etc/passwd-4242'],
            'a traversal in a bare key' => ['reports/../finalsource/x-4242'],
            'a dot segment' => ['reports/./x-4242'],
            'an unknown prefix' => ['s3://primemoverrunner/secrets/static-jobdata-visitjob-x-4242'],
            'no prefix at all' => ['s3://primemoverrunner/static-jobdata-visitjob-x-4242'],
            'a backslash' => ['reports\\static-jobdata-visitjob-x-4242'],
            'a nested scheme' => ['s3://primemoverrunner/reports/http://elsewhere/x-4242'],
            'another job, same shape' => ['s3://primemoverrunner/reports/static-jobdata-visitjob-youtube-4243'],
            'the id as a suffix of a longer id' => ['s3://primemoverrunner/reports/static-jobdata-visitjob-youtube-14242'],
            'empty' => [''],
        ];
    }

    /**
     * ...and the shapes the runner really writes still read.
     *
     * @covers ::storageKeyFor
     *
     * @dataProvider acceptedPaths
     */
    public function test_the_runners_own_object_keys_are_readable(string $path, string $expected): void
    {
        $this->assertSame($expected, Report::storageKeyFor($path, 4242));
    }

    /**
     * Every shape observed in the live store on 2026-09-08 (54,138 objects under
     * reports/, plus finalsource/), and the bare-key form for rows predating the
     * s3:// prefix.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function acceptedPaths(): array
    {
        return [
            'a decision payload' => [
                's3://primemoverrunner/reports/lambda-jobdata-lambdaselectjob-youtube_video_select-4242',
                'reports/lambda-jobdata-lambdaselectjob-youtube_video_select-4242',
            ],
            'a HAR' => [
                's3://primemoverrunner/reports/static-jobdata-visitjob-NULL-4242',
                'reports/static-jobdata-visitjob-NULL-4242',
            ],
            'a captcha dump with an empty task' => [
                's3://primemoverrunner/reports/static-captcha-entertextfieldjob--4242',
                'reports/static-captcha-entertextfieldjob--4242',
            ],
            'a rendered page' => [
                's3://primemoverrunner/finalsource/20260819-112708-page-finalsource-visitjob-NULL-4242',
                'finalsource/20260819-112708-page-finalsource-visitjob-NULL-4242',
            ],
            'the underscore identifier' => [
                's3://primemoverrunner/reports/lambda_jobdata_4242',
                'reports/lambda_jobdata_4242',
            ],
            'a bare key' => [
                'reports/static-jobdata-visitjob-NULL-4242',
                'reports/static-jobdata-visitjob-NULL-4242',
            ],
        ];
    }

    /**
     * The write end: a crafted row cannot be stored in the first place.
     *
     * @covers \App\Modules\Report\Http\Request\ReportStoreRequest::withValidator
     */
    public function test_a_report_cannot_be_filed_against_an_object_of_another_job(): void
    {
        $this->withExceptionHandling();

        $victimJob = $this->otherResearchersJob();
        $own = $this->ownJob();

        Passport::actingAs($this->user);
        $this->postJson('/api/v1/reports', [
            'job_id' => $own->id,
            'mimetype' => Report::S3MIMETYPE,
            'filename' => 'static-jobdata-visitjob-youtube-'.$victimJob->id,
            'description' => 'fixed job data',
            'path' => Report::S3PREFIX.'reports/static-jobdata-visitjob-youtube-'.$victimJob->id,
        ])->assertStatus(422)->assertJsonValidationErrors('path');

        $this->assertDatabaseMissing('reports', ['job_id' => $own->id, 'filename' => 'static-jobdata-visitjob-youtube-'.$victimJob->id]);
    }

    /**
     * The runner's own POST is untouched -- it is the only writer there is.
     *
     * @covers \App\Modules\Report\Http\Request\ReportStoreRequest::rules
     */
    public function test_the_runners_own_report_row_is_still_accepted(): void
    {
        $this->withExceptionHandling();

        $own = $this->ownJob();
        $identifier = 'static-jobdata-visitjob-NULL-'.$own->id;

        Passport::actingAs($this->user);
        $this->postJson('/api/v1/reports', [
            'job_id' => $own->id,
            'mimetype' => Report::S3MIMETYPE,
            'filename' => $identifier,
            'description' => 'fixed job data',
            'path' => Report::S3PREFIX.'reports/'.$identifier,
        ])->assertSuccessful();

        $this->assertDatabaseHas('reports', ['job_id' => $own->id, 'filename' => $identifier]);
    }

    private function ownJob(): Job
    {
        return Job::factory()->create([
            'queue_id' => $this->queue->id,
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * A job in a whole separate experiment, owned by somebody else.
     */
    private function otherResearchersJob(): Job
    {
        $stranger = User::factory()->create();
        $experiment = Experiment::factory()->create(['user_id' => $stranger->id]);
        $crawler = Crawler::factory()->create([
            'proxy_id' => $this->proxy->id,
            'agent_id' => $this->agent->id,
            'experiment_id' => $experiment->id,
            'configuration_id' => $this->configuration->id,
        ]);
        $queue = Queue::factory()->create(['crawler_id' => $crawler->id]);

        return Job::factory()->create(['queue_id' => $queue->id, 'user_id' => $stranger->id]);
    }
}
