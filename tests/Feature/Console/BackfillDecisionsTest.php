<?php

namespace Tests\Feature\Console;

use App\Modules\Job\Model\Job;
use App\Modules\Job\Service\JobDecisionService;
use App\Modules\Queue\Model\Queue;
use App\Modules\Report\Model\Report;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesDecisionPayloads;
use Tests\TestCase;

/**
 * php artisan decisions:backfill.
 *
 * The runner writes the verdict onto the status PUT from now on, so this
 * command is about the past: tens of thousands of lambda-jobdata objects were
 * written before the columns existed, and the answer to "did this study's
 * treatment arm actually get treated?" is inside every one of them and joinable
 * from none.
 *
 * Storage::fake throughout; nothing here touches the real MinIO.
 *
 * @internal
 *
 * @coversDefaultClass \App\Console\Commands\BackfillDecisions
 */
class BackfillDecisionsTest extends TestCase
{
    use MakesDecisionPayloads;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Report::S3DISK);
    }

    /**
     * @covers ::handle
     */
    public function test_it_populates_the_columns_from_the_stored_payload(): void
    {
        $job = $this->lambdaJob();
        $this->storeDecisionFor($job, $this->reportedPayload([
            'degraded' => true,
            'treatment_lost' => true,
            'selection_mode' => 'random',
            'filters' => ['political' => 'failed: boom', 'fringe' => 'skipped'],
        ]));

        $this->artisan('decisions:backfill')->assertSuccessful();

        $fresh = $job->fresh();

        $this->assertTrue($fresh->decision_degraded);
        $this->assertTrue($fresh->decision_treatment_lost);
        $this->assertSame('random', $fresh->decision_selection_mode);
        $this->assertSame('failed: boom', $fresh->decision_report['filters']['political']);
        // The multi-MB per-candidate score block never reaches the column.
        $this->assertArrayNotHasKey('procedure', $fresh->decision_report);
    }

    /**
     * Running it twice must change nothing the second time. The first run makes
     * every row it touched non-null, and the selection excludes non-null rows.
     *
     * @covers ::handle
     */
    public function test_it_is_idempotent(): void
    {
        $job = $this->lambdaJob();
        $this->storeDecisionFor($job, $this->reportedPayload(['degraded' => true, 'treatment_lost' => false]));

        $this->artisan('decisions:backfill')->assertSuccessful();
        $this->assertTrue($job->fresh()->decision_degraded);

        // Swap the stored object for a payload saying something else. If the
        // second run touched the row at all, the row would now say 'llm'.
        Storage::disk(Report::S3DISK)->put(
            'reports/'.JobDecisionService::JOBDATA_MARKER.'-lambdaselectjob-youtube_video_select-'.$job->id,
            $this->decisionZip($job, $this->reportedPayload(['degraded' => false, 'selection_mode' => 'llm']))
        );

        $this->artisan('decisions:backfill')->assertSuccessful();

        $second = $job->fresh();

        $this->assertTrue($second->decision_degraded, 'the second run must not rewrite the row');
        $this->assertFalse($second->decision_treatment_lost);
        $this->assertSame('random', $second->decision_selection_mode);
    }

    /**
     * @covers ::handle
     */
    public function test_dry_run_writes_nothing(): void
    {
        $job = $this->lambdaJob();
        $this->storeDecisionFor($job, $this->reportedPayload(['degraded' => true]));

        $this->artisan('decisions:backfill', ['--dry-run' => true])->assertSuccessful();

        $this->assertNull($job->fresh()->decision_degraded);
    }

    /**
     * The rule that keeps the command safe to run against production: a verdict
     * the runner recorded at the time beats one reconstructed later from an
     * artifact.
     *
     * @covers ::handle
     */
    public function test_it_never_overwrites_an_existing_verdict_without_force(): void
    {
        $job = $this->lambdaJob();
        $job->forceFill([
            'decision_degraded' => false,
            'decision_treatment_lost' => false,
            'decision_selection_mode' => 'llm',
        ])->save();

        $this->storeDecisionFor($job, $this->reportedPayload([
            'degraded' => true,
            'treatment_lost' => true,
            'selection_mode' => 'random',
        ]));

        $this->artisan('decisions:backfill')->assertSuccessful();

        $this->assertFalse($job->fresh()->decision_degraded, 'the recorded verdict must win');
        $this->assertSame('llm', $job->fresh()->decision_selection_mode);

        $this->artisan('decisions:backfill', ['--force' => true])->assertSuccessful();

        $this->assertTrue($job->fresh()->decision_degraded);
        $this->assertSame('random', $job->fresh()->decision_selection_mode);
    }

    /**
     * The second rule, and the one that decides whether the whole jobs table
     * ends up telling the truth: a payload with NO degradation report says
     * nothing, so nothing is written. Writing false would assert that thousands
     * of historical jobs applied treatments nobody measured.
     *
     * @covers ::handle
     */
    public function test_a_legacy_payload_leaves_the_columns_null(): void
    {
        $job = $this->lambdaJob();
        $this->storeDecisionFor($job, $this->legacyPayload());

        $this->artisan('decisions:backfill')
            ->expectsOutputToContain('no degradation report')
            ->assertSuccessful();

        $fresh = $job->fresh();

        $this->assertNull($fresh->decision_degraded);
        $this->assertNull($fresh->decision_treatment_lost);
        $this->assertNull($fresh->decision_report);
    }

    /**
     * An object the store will not give up is counted and reported, never
     * written as a verdict: missing evidence about that job, not evidence that
     * the job was fine. And it must not stop the run.
     *
     * @covers ::handle
     */
    public function test_an_unreadable_object_is_reported_and_does_not_stop_the_run(): void
    {
        $broken = $this->lambdaJob();
        $this->storeMissingDecisionFor($broken);

        $good = $this->lambdaJob();
        $this->storeDecisionFor($good, $this->reportedPayload(['degraded' => true]));

        $this->artisan('decisions:backfill')
            ->expectsOutputToContain('unreadable')
            ->assertSuccessful();

        $this->assertNull($broken->fresh()->decision_degraded);
        $this->assertTrue($good->fresh()->decision_degraded);
    }

    /**
     * @covers ::handle
     */
    public function test_limit_stops_early(): void
    {
        $jobs = [];

        for ($i = 0; $i < 3; $i++) {
            $job = $this->lambdaJob();
            $this->storeDecisionFor($job, $this->reportedPayload(['degraded' => true]));
            $jobs[] = $job;
        }

        $this->artisan('decisions:backfill', ['--limit' => 1])->assertSuccessful();

        $done = collect($jobs)->filter(fn (Job $job): bool => null !== $job->fresh()->decision_degraded);

        $this->assertCount(1, $done);
    }

    /**
     * @covers ::handle
     */
    public function test_it_can_be_pointed_at_one_job(): void
    {
        $wanted = $this->lambdaJob();
        $this->storeDecisionFor($wanted, $this->reportedPayload(['degraded' => true]));

        $other = $this->lambdaJob();
        $this->storeDecisionFor($other, $this->reportedPayload(['degraded' => true]));

        $this->artisan('decisions:backfill', ['--job' => [$wanted->id]])->assertSuccessful();

        $this->assertTrue($wanted->fresh()->decision_degraded);
        $this->assertNull($other->fresh()->decision_degraded);
    }

    /**
     * Most jobs never call the decision function. Walking all of them would be
     * a bucket listing per job; the reports table is the index instead.
     *
     * @covers ::handle
     */
    public function test_jobs_without_a_jobdata_report_are_not_visited(): void
    {
        $plain = $this->lambdaJob();

        Report::factory()->create([
            'job_id' => $plain->id,
            'user_id' => $plain->user_id,
            'filename' => 'static-jobdata-visitjob-youtube-'.$plain->id,
            'path' => Report::S3PREFIX.'reports/static-jobdata-visitjob-youtube-'.$plain->id,
        ]);

        $this->artisan('decisions:backfill')->assertSuccessful();

        $this->assertNull($plain->fresh()->decision_degraded);
    }

    /**
     * The backfill reads the past; it does not rewrite it.
     *
     * updated_at is an allowed sort on /jobs and the field a GUI reaches for to
     * show "what happened recently". Letting a backfill of tens of thousands of
     * archived jobs touch it would date every one of them to the day the
     * command ran and bury the jobs that actually changed.
     *
     * @covers ::handle
     */
    public function test_it_does_not_touch_updated_at(): void
    {
        $job = $this->lambdaJob();
        $this->storeDecisionFor($job, $this->reportedPayload(['degraded' => true]));

        $job->timestamps = false;
        $job->forceFill(['updated_at' => '2024-01-02 03:04:05'])->save();
        $job->timestamps = true;

        $before = $job->fresh()->updated_at;

        $this->artisan('decisions:backfill')->assertSuccessful();

        $fresh = $job->fresh();

        $this->assertTrue($fresh->decision_degraded, 'the verdict should still have been written');
        $this->assertSame(
            $before->toDateTimeString(),
            $fresh->updated_at->toDateTimeString(),
            'the backfill moved updated_at, which redates the whole archive'
        );
    }

    /**
     * The write re-asserts updated_at, so a schema that redates it cannot.
     *
     * The assertion above can only ever pass on this suite: it runs on in-memory
     * SQLite, where Laravel's SQLiteGrammar rewrites the column default to a
     * plain CURRENT_TIMESTAMP and the ON UPDATE clause the 2020 migration asks
     * for (`->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'))`)
     * cannot exist. On MySQL, ON UPDATE CURRENT_TIMESTAMP is applied by the
     * SERVER to every UPDATE that does not assign the column -- $timestamps =
     * false does not reach it. So the thing worth asserting is the statement:
     * updated_at must appear in the SET clause, carrying its archived value.
     *
     * (Measured 2026-09-08: this deployment's jobs.updated_at happens to carry
     * no ON UPDATE clause, so the old code would not have redated it here. The
     * command writes 31,600 archived rows once and irreversibly; which framework
     * version created the table is not a fact it should depend on.)
     *
     * @covers ::handle
     */
    public function test_the_write_re_asserts_updated_at_in_the_statement(): void
    {
        $job = $this->lambdaJob();
        $this->storeDecisionFor($job, $this->reportedPayload(['degraded' => true]));

        $job->timestamps = false;
        $job->forceFill(['updated_at' => '2024-01-02 03:04:05'])->save();
        $job->timestamps = true;

        $archived = $job->fresh()->getRawOriginal('updated_at');

        /** @var array<int, \Illuminate\Database\Events\QueryExecuted> $verdictWrites */
        $verdictWrites = [];
        DB::listen(function ($query) use (&$verdictWrites): void {
            $sql = mb_strtolower($query->sql);

            if (str_starts_with($sql, 'update') && str_contains($sql, 'jobs') && str_contains($sql, 'decision_degraded')) {
                $verdictWrites[] = $query;
            }
        });

        $this->artisan('decisions:backfill')->assertSuccessful();

        $this->assertCount(1, $verdictWrites, 'the backfill should write the verdict in one statement');

        $this->assertStringContainsString(
            'updated_at',
            $verdictWrites[0]->sql,
            'updated_at is absent from the SET clause, so a column declared ON UPDATE CURRENT_TIMESTAMP would be redated by MySQL'
        );
        $this->assertContains(
            $archived,
            $verdictWrites[0]->bindings,
            'updated_at should be re-asserted with the value the archive already held'
        );

        // ...and the row itself is unchanged, which is the point of all of it.
        $this->assertSame($archived, $job->fresh()->getRawOriginal('updated_at'));
        $this->assertTrue($job->fresh()->decision_degraded);

        // The statement is keyed, so no other job was written on the way past.
        $this->assertNull(
            Job::query()->where('id', '!=', $job->id)->orderBy('id')->firstOrFail()->decision_degraded
        );
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
