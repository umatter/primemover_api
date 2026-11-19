<?php

namespace Tests\Feature\Modules\Experiment\Http\Controller\Api\v1;

use App\Models\User;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Experiment\Service\ExperimentHealth;
use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * GET /api/v1/experiments/{id}/health.
 *
 * An experiment has no status of any kind -- no column, no aggregate -- so
 * "which of my studies needs attention?" could only be answered by paging
 * through every crawler 15 at a time and summing the rollups client-side. Which
 * means it was never answered: the warning state the 2026-08 overhaul
 * introduced reached the job row, the run row and the bot card, and stopped one
 * level below the screen where triage starts.
 *
 * The three rules these tests exist to pin down, all from
 * docs/integration-contract.md 3.4:
 *
 * - every bucket is alias-aware, because 'failed' and 'failure' are one outcome
 *   spelled two ways and this database holds hundreds of the legacy spelling;
 * - 'unknown' is a REPORTED bucket -- never folded into success, never dropped,
 *   so the six buckets always sum to total;
 * - bad_* is failure OR warning, never failure alone.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Experiment\Service\ExperimentHealth
 */
class ExperimentHealthTest extends TestCase
{
    private Experiment $study;

    private Crawler $bot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->study = Experiment::factory()->create(['user_id' => $this->user->id]);
        $this->bot = $this->botFor($this->study);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * The contract in one assertion: nothing is lost between the buckets and
     * the total, whatever the runner spelled.
     *
     * @covers ::bucket
     * @covers ::for
     */
    public function test_the_six_run_buckets_account_for_every_run_in_the_window(): void
    {
        $this->runWithStatus(Queue::STATUS_RUNNING);
        $this->runWithStatus(Queue::STATUS_SUCCESS);
        $this->runWithStatus(Queue::STATUS_SUCCESS);
        $this->runWithStatus(Queue::STATUS_WARNING);
        $this->runWithStatus(Queue::STATUS_FAILURE);
        $this->runWithStatus(Queue::STATUS_SKIPPED);
        $this->runWithStatus(null);

        $runs = $this->health()['runs'];

        $this->assertSame(
            [
                'total' => 7,
                'running' => 1,
                'success' => 2,
                'warning' => 1,
                'failure' => 1,
                'skipped' => 1,
                'unknown' => 1,
            ],
            $runs
        );

        $this->assertSame(
            $runs['total'],
            $runs['running'] + $runs['success'] + $runs['warning']
                + $runs['failure'] + $runs['skipped'] + $runs['unknown'],
            'a run that is in no bucket is a run nobody will ever look at'
        );
    }

    /**
     * The jobs tally obeys the same rule, and is scoped through its run.
     *
     * @covers ::bucket
     * @covers ::for
     */
    public function test_the_six_job_buckets_account_for_every_job_of_those_runs(): void
    {
        $run = $this->runWithStatus(Queue::STATUS_WARNING);
        $this->jobsFor($run, [
            Queue::STATUS_SUCCESS,
            Queue::STATUS_SUCCESS,
            Queue::STATUS_WARNING,
            Queue::STATUS_FAILURE,
            Queue::STATUS_SKIPPED,
            Queue::STATUS_RUNNING,
            null,
        ]);

        $jobs = $this->health()['jobs'];

        $this->assertSame(
            [
                'total' => 7,
                'running' => 1,
                'success' => 2,
                'warning' => 1,
                'failure' => 1,
                'skipped' => 1,
                'unknown' => 1,
            ],
            $jobs
        );
    }

    /**
     * 256 job rows and 277 queue rows on the production database spell it
     * 'failed', and 'skpped' is a runner typo that persists in everything
     * written before 2026-08-27. A tally that matched only the canonical
     * spelling would report a study with a bad night as almost clean.
     *
     * @covers ::bucket
     */
    public function test_legacy_spellings_land_in_the_bucket_they_mean(): void
    {
        $legacy = $this->runWithStatus('failed');
        $this->jobsFor($legacy, ['failed', 'skpped', 'error', 'warn', 'done']);
        $this->runWithStatus('skpped');
        $this->runWithStatus(Queue::STATUS_FAILURE);

        $health = $this->health();

        $this->assertSame(2, $health['runs']['failure'], "'failed' is 'failure'");
        $this->assertSame(1, $health['runs']['skipped'], "'skpped' is the runner's own typo");
        $this->assertSame(0, $health['runs']['unknown']);

        $this->assertSame(2, $health['jobs']['failure']);
        $this->assertSame(1, $health['jobs']['skipped']);
        $this->assertSame(1, $health['jobs']['warning']);
        $this->assertSame(1, $health['jobs']['success']);
        $this->assertSame(0, $health['jobs']['unknown']);
    }

    /**
     * The one rule that is easiest to break by accident and worst to break:
     * an unrecognised value is UNKNOWN, never success.
     *
     * @covers ::bucket
     */
    public function test_an_unrecognised_status_is_unknown_and_never_success(): void
    {
        $run = $this->runWithStatus('exploded');
        $this->jobsFor($run, ['exploded', '', null]);
        $this->runWithStatus(Queue::STATUS_SUCCESS);

        $health = $this->health();

        $this->assertSame(1, $health['runs']['unknown']);
        $this->assertSame(1, $health['runs']['success'], 'the unrecognised run must not have been counted as one');
        $this->assertSame(2, $health['runs']['total']);

        // '' and NULL are unknown for the same reason a typo is: nobody can say
        // what happened.
        $this->assertSame(3, $health['jobs']['unknown']);
        $this->assertSame(0, $health['jobs']['success']);
    }

    /**
     * bad = failure OR warning. Never failure alone -- a run that completed
     * having lost half its jobs is exactly the one nobody would look at
     * otherwise, and that is what the warning state was introduced for.
     *
     * @covers ::badTotal
     */
    public function test_bad_counts_warning_as_well_as_failure(): void
    {
        $warned = $this->runWithStatus(Queue::STATUS_WARNING);
        $this->jobsFor($warned, [Queue::STATUS_WARNING, Queue::STATUS_FAILURE, Queue::STATUS_SUCCESS]);
        $this->runWithStatus('failed');
        $this->runWithStatus(Queue::STATUS_SUCCESS);
        $this->runWithStatus(Queue::STATUS_SKIPPED);

        $health = $this->health();

        $this->assertSame(2, $health['bad_runs'], 'one warning + one legacy failure');
        $this->assertSame(2, $health['bad_jobs']);
        $this->assertNotSame($health['runs']['failure'], $health['bad_runs'], 'bad_runs is not is_failure');
    }

    /**
     * @covers ::for
     */
    public function test_the_window_is_a_rolling_24_hours_by_default(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-07 10:00:00'));

        // 20 hours ago: inside the window, but on the previous calendar day.
        $this->runWithStatus(Queue::STATUS_FAILURE, '2026-09-06 14:00:00');
        // 44 hours ago: genuinely outside it.
        $this->runWithStatus(Queue::STATUS_FAILURE, '2026-09-05 14:00:00');

        $this->assertSame(1, $this->health()['runs']['total']);
    }

    /**
     * @covers ::for
     * @covers ::clampHours
     */
    public function test_hours_widens_the_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-07 10:00:00'));

        $this->runWithStatus(Queue::STATUS_FAILURE, '2026-09-06 14:00:00');
        $old = $this->runWithStatus(Queue::STATUS_WARNING, '2026-09-01 14:00:00');
        $this->jobsFor($old, [Queue::STATUS_WARNING]);

        $default = $this->health();
        $this->assertSame(1, $default['runs']['total']);
        $this->assertSame(24, $default['window_hours']);
        $this->assertSame(0, $default['jobs']['total'], 'the old run\'s jobs are outside the window too');

        $week = $this->health(['hours' => 168]);
        $this->assertSame(2, $week['runs']['total']);
        $this->assertSame(168, $week['window_hours']);
        $this->assertSame(2, $week['bad_runs']);
        $this->assertSame(1, $week['jobs']['warning']);
    }

    /**
     * Clamped, never rejected. This endpoint answers a dashboard: a 400 because
     * someone typed hours=0 would be worse than the 1-hour window they nearly
     * asked for, and window_hours reports back what was actually used.
     *
     * @covers ::clampHours
     */
    public function test_hours_is_clamped_rather_than_rejected(): void
    {
        $this->assertSame(1, $this->health(['hours' => 0])['window_hours']);
        $this->assertSame(1, $this->health(['hours' => -5])['window_hours']);
        $this->assertSame(720, $this->health(['hours' => 99999])['window_hours']);
        $this->assertSame(24, $this->health(['hours' => 'all'])['window_hours']);
        $this->assertSame(24, $this->health(['hours' => ''])['window_hours']);
        $this->assertSame(720, $this->health(['hours' => 720])['window_hours']);
    }

    /**
     * A study nobody has run yet is a perfectly ordinary thing to open, and it
     * must answer with zeros rather than with a 500 or an empty body.
     *
     * @covers ::for
     */
    public function test_an_experiment_with_no_runs_answers_with_zeros(): void
    {
        $health = $this->health();

        $this->assertSame(1, $health['bots'], 'it has a bot; it has just never run');
        $this->assertSame(0, $health['runs']['total']);
        $this->assertSame(0, $health['jobs']['total']);
        $this->assertSame(0, $health['bad_runs']);
        $this->assertSame(0, $health['bad_jobs']);
        $this->assertSame(0, $health['treatment_lost_jobs']);
        $this->assertSame(0, $health['degraded_jobs']);
        $this->assertNull($health['last_finished_at']);

        foreach (ExperimentHealth::BUCKETS as $bucket) {
            $this->assertSame(0, $health['runs'][$bucket], "runs.{$bucket}");
            $this->assertSame(0, $health['jobs'][$bucket], "jobs.{$bucket}");
        }
    }

    /**
     * An experiment with no bots at all -- freshly created, nothing uploaded --
     * is the other empty shape, and it must not divide by anything either.
     *
     * @covers ::for
     */
    public function test_an_experiment_with_no_bots_answers_with_zeros(): void
    {
        $empty = Experiment::factory()->create(['user_id' => $this->user->id]);

        $health = $this->health([], $empty);

        $this->assertSame(0, $health['bots']);
        $this->assertSame(0, $health['runs']['total']);
        $this->assertNull($health['last_finished_at']);
    }

    /**
     * Only this study's runs.
     *
     * @covers ::for
     */
    public function test_another_experiments_runs_are_not_counted(): void
    {
        $this->runWithStatus(Queue::STATUS_FAILURE);

        $other = Experiment::factory()->create(['user_id' => $this->user->id]);
        $otherBot = $this->botFor($other);
        $this->runWithStatus(Queue::STATUS_FAILURE, null, $otherBot);
        $this->runWithStatus(Queue::STATUS_FAILURE, null, $otherBot);

        $this->assertSame(1, $this->health()['runs']['total']);
        $this->assertSame(2, $this->health([], $other)['runs']['total']);
        $this->assertSame(1, $this->health()['bots']);
    }

    /**
     * The verdict counts are not statuses: they read three-state columns where
     * NULL means UNKNOWN. Only true is counted, in either direction, so a study
     * whose jobs all predate the runner deploy reports 0 while knowing nothing
     * at all -- and a job that lost its treatment is routinely 'success',
     * because nothing about it failed.
     *
     * @covers ::for
     */
    public function test_the_decision_verdicts_count_only_an_explicit_true(): void
    {
        $run = $this->runWithStatus(Queue::STATUS_SUCCESS);

        $this->jobFor($run, Queue::STATUS_SUCCESS, [
            'decision_degraded' => true,
            'decision_treatment_lost' => true,
        ]);
        $this->jobFor($run, Queue::STATUS_SUCCESS, [
            'decision_degraded' => true,
            'decision_treatment_lost' => false,
        ]);
        $this->jobFor($run, Queue::STATUS_SUCCESS, [
            'decision_degraded' => false,
            'decision_treatment_lost' => false,
        ]);
        // The legacy job: no verdict at all. Unknown, and unknown is not clean.
        $this->jobFor($run, Queue::STATUS_SUCCESS);

        $health = $this->health();

        $this->assertSame(2, $health['degraded_jobs']);
        $this->assertSame(1, $health['treatment_lost_jobs']);
        $this->assertSame(4, $health['jobs']['success'], 'losing a treatment fails nothing');
        $this->assertSame(0, $health['bad_jobs'], 'and it is not a bad STATUS -- which is the whole problem');
    }

    /**
     * last_finished_at is deliberately not window-scoped: with every other
     * number zero, "last finished five days ago" and "never ran" are completely
     * different situations, and a window-scoped null could not tell them apart.
     *
     * @covers ::for
     */
    public function test_last_finished_at_reaches_back_beyond_the_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-07 10:00:00'));

        $old = $this->runWithStatus(Queue::STATUS_SUCCESS, '2026-09-01 14:00:00');
        $old->update(['finished_at' => '2026-09-01 15:30:00']);

        $health = $this->health();

        $this->assertSame(0, $health['runs']['total'], 'nothing ran in the last 24h');
        $this->assertNotNull($health['last_finished_at']);

        // Serialised exactly the way the run's own finished_at is, so a
        // consumer can compare the two without knowing there are two code
        // paths behind them.
        $this->assertSame($old->fresh()->toArray()['finished_at'], $health['last_finished_at']);
    }

    /**
     * The newest finish wins, and an empty finished_at is not a timestamp.
     *
     * @covers ::for
     */
    public function test_last_finished_at_is_the_newest_finish(): void
    {
        $this->runWithStatus(Queue::STATUS_SUCCESS)->update(['finished_at' => '2026-09-05 09:00:00']);

        $newest = $this->runWithStatus(Queue::STATUS_SUCCESS);
        $newest->update(['finished_at' => '2026-09-06 21:15:00']);

        // A run in flight: finished_at is empty, which is not a timestamp.
        $this->runWithStatus(Queue::STATUS_RUNNING)->update(['finished_at' => '']);

        $this->assertSame($newest->fresh()->toArray()['finished_at'], $this->health()['last_finished_at']);
    }

    /**
     * The property that makes this endpoint worth having: its cost does not
     * grow with the study. A GUI summing client-side pays one request per page
     * of crawlers and one per run; this pays the same six queries for
     * a study with 3 runs and for one with 240.
     *
     * @covers ::for
     */
    public function test_the_cost_is_constant_in_the_size_of_the_study(): void
    {
        $small = $this->queriesForHealthOf($this->study);

        $big = Experiment::factory()->create(['user_id' => $this->user->id]);

        for ($b = 0; $b < 8; $b++) {
            $bot = $this->botFor($big);

            for ($r = 0; $r < 10; $r++) {
                $run = $this->runWithStatus(Queue::STATUS_SUCCESS, null, $bot);
                $this->jobsFor($run, [Queue::STATUS_SUCCESS, Queue::STATUS_WARNING]);
            }
        }

        $large = $this->queriesForHealthOf($big);

        $this->assertSame($small, $large, 'the query count must not scale with bots or runs');
        $this->assertLessThanOrEqual(6, $large, "the health of one study cost {$large} queries");
    }

    /**
     * @covers ::for
     */
    public function test_the_endpoint_serves_the_health_object(): void
    {
        $run = $this->runWithStatus(Queue::STATUS_WARNING);
        $this->jobsFor($run, [Queue::STATUS_WARNING, Queue::STATUS_SUCCESS]);

        Passport::actingAs($this->user);

        $body = $this->getJson('/api/v1/experiments/'.$this->study->id.'/health')
            ->assertOk()
            ->json('data');

        $this->assertSame($this->study->id, $body['experiment_id']);
        $this->assertSame(24, $body['window_hours']);
        $this->assertSame(1, $body['bots']);
        $this->assertSame(1, $body['runs']['warning']);
        $this->assertSame(1, $body['bad_runs']);
        $this->assertSame(1, $body['bad_jobs']);
        $this->assertArrayHasKey('unknown', $body['jobs']);
        $this->assertArrayHasKey('last_finished_at', $body);
    }

    /**
     * Same policy as GET /experiments/{id}: a researcher must not read another
     * study's health by guessing an id.
     *
     * @covers ::for
     */
    public function test_another_researcher_cannot_read_the_health(): void
    {
        $this->withExceptionHandling();

        $stranger = User::factory()->create();
        config([
            'primemover.isolation.enabled' => true,
            'primemover.isolation.service_account_ids' => [],
        ]);

        Passport::actingAs($stranger);
        $this->getJson('/api/v1/experiments/'.$this->study->id.'/health')->assertForbidden();

        Passport::actingAs($this->user);
        $this->getJson('/api/v1/experiments/'.$this->study->id.'/health')->assertOk();
    }

    /**
     * @covers ::for
     */
    public function test_an_unauthenticated_caller_is_refused(): void
    {
        $this->withExceptionHandling();

        $this->getJson('/api/v1/experiments/'.$this->study->id.'/health')->assertUnauthorized();
    }

    /**
     * Experiment 240: four bots failed to start every run for a week and no
     * number anywhere said "this bot is dead".
     *
     * @covers ::for
     */
    public function test_a_bot_whose_latest_runs_never_started_is_listed_with_its_streak(): void
    {
        $dead = $this->botFor($this->study);

        $ok = $this->runWithStatus(Queue::STATUS_SUCCESS, $this->hoursAgo(20), $dead);
        $this->jobsFor($ok, [Queue::STATUS_SUCCESS]);

        $first = $this->neverStarted($dead, $this->hoursAgo(9));
        $this->neverStarted($dead, $this->hoursAgo(6));
        $latest = $this->neverStarted($dead, $this->hoursAgo(3), 'initialisation failed - Multilogin LOCK_PROFILE_ERROR');

        $listed = $this->health()['bots_not_starting'];

        $this->assertCount(1, $listed);
        $this->assertSame($dead->id, $listed[0]['crawler_id']);
        $this->assertSame($dead->name, $listed[0]['name']);
        $this->assertSame(3, $listed[0]['consecutive']);
        $this->assertSame(
            Carbon::parse($first->fresh()->getRawOriginal('started_at'))->toIso8601String(),
            $listed[0]['since']
        );
        $this->assertSame($latest->id, $listed[0]['latest_run_id']);
        $this->assertSame('initialisation failed - Multilogin LOCK_PROFILE_ERROR', $listed[0]['reason']);
    }

    /**
     * "Latest" is the point: a bot that failed to start this morning and has
     * run since is not the bot to look at.
     *
     * @covers ::for
     */
    public function test_a_bot_that_started_again_is_not_listed(): void
    {
        $this->neverStarted($this->bot, $this->hoursAgo(9));
        $this->neverStarted($this->bot, $this->hoursAgo(6));
        $ran = $this->runWithStatus(Queue::STATUS_WARNING, $this->hoursAgo(3));
        $ran->update(['processed' => 1]);
        $this->jobsFor($ran, [Queue::STATUS_SUCCESS, Queue::STATUS_WARNING]);

        $this->assertSame([], $this->health()['bots_not_starting']);
    }

    /**
     * A run that failed AFTER it started is a failed run, not a bot that does
     * not start; and a run still open says nothing yet.
     *
     * @covers ::for
     */
    public function test_a_run_that_failed_after_starting_and_an_open_run_do_not_count(): void
    {
        $midway = $this->runWithStatus(Queue::STATUS_FAILURE, $this->hoursAgo(5));
        $midway->update(['processed' => 1]);
        $this->jobsFor($midway, [Queue::STATUS_SUCCESS, Queue::STATUS_FAILURE, Queue::STATUS_SKIPPED]);

        $other = $this->botFor($this->study);
        $this->neverStarted($other, $this->hoursAgo(4));
        $open = $this->runWithStatus(Queue::STATUS_RUNNING, $this->hoursAgo(1), $other);
        $open->update(['processed' => 0]);

        $listed = $this->health()['bots_not_starting'];

        $this->assertCount(1, $listed, 'the open run does not hide the streak before it');
        $this->assertSame($other->id, $listed[0]['crawler_id']);
        $this->assertSame(1, $listed[0]['consecutive']);
    }

    /**
     * Both shapes of a never-started run count: jobs with no status (reported
     * before the API marked them) and jobs marked 'skipped' (after), and the
     * legacy 'failed' spelling of the run.
     *
     * @covers ::for
     */
    public function test_old_and_new_rows_count_alike_and_the_longest_streak_comes_first(): void
    {
        $old = $this->runWithStatus('failed', $this->hoursAgo(8));
        $old->update(['processed' => 1]);
        $this->jobsFor($old, [null, null]);
        $new = $this->neverStarted($this->bot, $this->hoursAgo(4));
        $new->skipUnstartedJobs();

        $other = $this->botFor($this->study);
        $this->neverStarted($other, $this->hoursAgo(2));

        $listed = $this->health()['bots_not_starting'];

        $this->assertSame([$this->bot->id, $other->id], array_column($listed, 'crawler_id'));
        $this->assertSame([2, 1], array_column($listed, 'consecutive'));
    }

    /**
     * The streak is counted inside the window, like every other number here.
     *
     * @covers ::for
     */
    public function test_the_streak_is_counted_inside_the_window(): void
    {
        $this->neverStarted($this->bot, $this->hoursAgo(40));
        $this->neverStarted($this->bot, $this->hoursAgo(3));

        $this->assertSame(1, $this->health()['bots_not_starting'][0]['consecutive']);
        $this->assertSame(2, $this->health(['hours' => 48])['bots_not_starting'][0]['consecutive']);
    }

    private function hoursAgo(int $hours): string
    {
        return Carbon::now()->subHours($hours)->format('Y-m-d H:i:s');
    }

    private function neverStarted(Crawler $bot, string $startedAt, string $message = 'initialisation failed'): Queue
    {
        $run = Queue::factory()->create([
            'crawler_id' => $bot->id,
            'status_code' => Queue::STATUS_FAILURE,
            'status_message' => $message,
            'started_at' => $startedAt,
            'processed' => 1,
        ]);
        $this->jobsFor($run, [null, null]);

        return $run;
    }

    /**
     * @return array<string, mixed>
     */
    private function health(array $query = [], ?Experiment $experiment = null): array
    {
        return app(ExperimentHealth::class)
            ->for($experiment ?? $this->study, $query['hours'] ?? null);
    }

    private function queriesForHealthOf(Experiment $experiment): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        app(ExperimentHealth::class)->for($experiment);

        $queries = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $queries;
    }

    private function botFor(Experiment $experiment): Crawler
    {
        return Crawler::factory()->create([
            'user_id' => $this->user->id,
            'experiment_id' => $experiment->id,
            'proxy_id' => $this->proxy->id,
            'agent_id' => $this->agent->id,
            'configuration_id' => $this->configuration->id,
            'active' => 1,
        ]);
    }

    private function runWithStatus(?string $status, ?string $startedAt = null, ?Crawler $bot = null): Queue
    {
        return Queue::factory()->create([
            'crawler_id' => ($bot ?? $this->bot)->id,
            'status_code' => $status,
            'started_at' => $startedAt ?? Carbon::now()->subHours(2)->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param array<int, string|null> $statuses
     */
    private function jobsFor(Queue $queue, array $statuses): void
    {
        foreach ($statuses as $status) {
            $this->jobFor($queue, $status);
        }
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function jobFor(Queue $queue, ?string $status, array $attributes = []): Job
    {
        return Job::factory()->create(array_merge([
            'queue_id' => $queue->id,
            'user_id' => $this->user->id,
            'status_code' => $status,
        ], $attributes));
    }
}
