<?php

namespace Tests\Feature\Modules\Crawler\Model;

use App\Modules\Crawler\Model\Crawler;
use App\Modules\Crawler\Repository\CrawlerRepository;
use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The crawler's dashboard rollups.
 *
 * Regression cover for the one-word bug behind "0 failed": the accessor asked
 * for status_code = 'failed' while every failing JoblistState the runner writes
 * carries 'failure', so no run the runner had failed could ever be counted and
 * the GUI's per-bot progress bars stayed green.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Crawler\Model\Crawler
 */
class CrawlerQueueStatusTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @covers ::getLatestQueuesFailedAttribute
     * @covers ::getQueuesByStatus
     */
    public function test_failed_runs_are_counted_from_the_code_the_runner_writes(): void
    {
        $startedAt = Carbon::now()->subHours(2)->format('Y-m-d H:i:s');

        $this->runWithStatus(Queue::STATUS_FAILURE, $startedAt);
        $this->runWithStatus(Queue::STATUS_SUCCESS, $startedAt);
        $this->runWithStatus(Queue::STATUS_SUCCESS, $startedAt);

        $this->assertSame(1, $this->crawler->latest_queues_failed);
        $this->assertSame(2, $this->crawler->latest_queues_succeeded);
        $this->assertSame(3, $this->crawler->latest_queues_count);
    }

    /**
     * Rows written before the fix carry 'failed' (277 queue rows on
     * 2026-08-27); they have to keep counting.
     *
     * @covers ::getLatestQueuesFailedAttribute
     */
    public function test_the_legacy_spelling_still_counts(): void
    {
        $startedAt = Carbon::now()->subHours(2)->format('Y-m-d H:i:s');

        $this->runWithStatus('failed', $startedAt);
        $this->runWithStatus(Queue::STATUS_FAILURE, $startedAt);

        $this->assertSame(2, $this->crawler->latest_queues_failed);
    }

    /**
     * whereDate() compared calendar dates, so a "24 hours back" window really
     * meant "since midnight" and dropped a run that failed last night.
     *
     * @covers ::getQueuesByStatus
     */
    public function test_the_window_is_a_rolling_24_hours_not_since_midnight(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-27 10:00:00'));

        // 20 hours ago -- inside the window, but on the previous calendar day.
        $this->runWithStatus(Queue::STATUS_FAILURE, '2026-08-26 14:00:00');
        // 44 hours ago -- genuinely outside it.
        $this->runWithStatus(Queue::STATUS_FAILURE, '2026-08-25 14:00:00');

        $this->assertSame(1, $this->crawler->latest_queues_failed);
        $this->assertSame(1, $this->crawler->latest_queues_count);
    }

    /**
     * The two queues the base fixture creates have no started_at at all; they
     * must not leak into a window count.
     *
     * @covers ::getQueuesByStatus
     */
    public function test_runs_that_never_started_are_not_in_the_window(): void
    {
        $this->assertSame(0, $this->crawler->latest_queues_count);
    }

    /**
     * The GUI's "Degraded" tile. A degraded run is not a failed run: its own
     * status_code is 'success', because every job in it finished. What went
     * wrong is one level down, in a job that came back 'warning' -- lost job
     * data, or a filter the experiment asked for that the decision function
     * could not apply. Counting the queue row alone can never see it.
     *
     * @covers ::getLatestQueuesDegradedAttribute
     * @covers ::queuesInWindowDegraded
     */
    public function test_degraded_runs_are_counted_from_their_jobs_not_their_own_status(): void
    {
        $startedAt = Carbon::now()->subHours(2)->format('Y-m-d H:i:s');

        $degraded = $this->runWithStatus(Queue::STATUS_SUCCESS, $startedAt);
        $this->jobsFor($degraded, [Queue::STATUS_SUCCESS, Queue::STATUS_WARNING]);

        $clean = $this->runWithStatus(Queue::STATUS_SUCCESS, $startedAt);
        $this->jobsFor($clean, [Queue::STATUS_SUCCESS, Queue::STATUS_SUCCESS]);

        $this->assertSame(1, $this->crawler->latest_queues_degraded);
        // ...and it is still a successful run by its own status_code, which is
        // exactly why the tile has to exist.
        $this->assertSame(2, $this->crawler->latest_queues_succeeded);
        $this->assertSame(0, $this->crawler->latest_queues_failed);
        $this->assertSame(2, $this->crawler->latest_queues_count);
    }

    /**
     * It counts runs, not warnings: three degraded jobs in one session are one
     * degraded session.
     *
     * @covers ::getLatestQueuesDegradedAttribute
     */
    public function test_a_run_with_several_warning_jobs_counts_once(): void
    {
        $run = $this->runWithStatus(Queue::STATUS_SUCCESS, Carbon::now()->subHours(2)->format('Y-m-d H:i:s'));
        $this->jobsFor($run, [Queue::STATUS_WARNING, Queue::STATUS_WARNING, Queue::STATUS_WARNING]);

        $this->assertSame(1, $this->crawler->latest_queues_degraded);
    }

    /**
     * @covers ::getLatestQueuesDegradedAttribute
     */
    public function test_degraded_runs_outside_the_window_do_not_count(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-27 10:00:00'));

        $old = $this->runWithStatus(Queue::STATUS_SUCCESS, '2026-08-25 14:00:00');
        $this->jobsFor($old, [Queue::STATUS_WARNING]);

        $this->assertSame(0, $this->crawler->latest_queues_degraded);
    }

    /**
     * A run whose jobs are all clean is not degraded, and one that never ran a
     * job is not either -- the tile must not light up on an empty run.
     *
     * @covers ::getLatestQueuesDegradedAttribute
     */
    public function test_a_run_with_no_warning_jobs_is_not_degraded(): void
    {
        $startedAt = Carbon::now()->subHours(2)->format('Y-m-d H:i:s');

        $failed = $this->runWithStatus(Queue::STATUS_FAILURE, $startedAt);
        $this->jobsFor($failed, [Queue::STATUS_FAILURE, Queue::STATUS_SKIPPED]);
        $this->runWithStatus(Queue::STATUS_SUCCESS, $startedAt);

        $this->assertSame(0, $this->crawler->latest_queues_degraded);
        $this->assertSame(1, $this->crawler->latest_queues_failed);
    }

    /**
     * The rollups are serialised on every crawler row of /crawlers and, because
     * that endpoint eager-loads the crawler, of /queues too.
     *
     * They used to be three appended accessors, each running its own count:
     * three queries per row, 157 for a 50-row page, and adding the degraded
     * tile as a fourth accessor would have made it 207. As count subselects on
     * the loading query the whole family is free, and the number below does not
     * move when the page gets longer -- which is the property worth protecting.
     *
     * @covers ::scopeExperiment
     */
    public function test_the_rollups_do_not_fan_out_into_a_query_per_row(): void
    {
        $this->crawlersWithRuns(50);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $page = app(CrawlerRepository::class)->all(50);
        $serialised = $page->toArray();
        $queries = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertCount(50, $page->items());
        $this->assertLessThanOrEqual(
            10,
            $queries,
            "a 50-row crawler page cost {$queries} queries; a per-row accessor would make it ~157"
        );

        $row = $serialised['data'][0];
        $this->assertArrayHasKey('latest_queues_degraded', $row);
        $this->assertSame(1, $row['latest_queues_succeeded']);
        $this->assertSame(1, $row['latest_queues_degraded']);
    }

    /**
     * A crawler that was loaded by a query without the subselects -- built in
     * memory, or hydrated by something that replaced the select list -- still
     * has to answer, and must fetch all six rollups in one aggregate rather
     * than one query each.
     *
     * @covers ::getLatestQueuesDegradedAttribute
     * @covers ::getLatestQueuesWarningAttribute
     */
    public function test_a_crawler_loaded_without_the_subselects_costs_one_query_for_all_five(): void
    {
        $startedAt = Carbon::now()->subHours(2)->format('Y-m-d H:i:s');
        $degraded = $this->runWithStatus(Queue::STATUS_SUCCESS, $startedAt);
        $this->jobsFor($degraded, [Queue::STATUS_WARNING]);
        $this->runWithStatus(Queue::STATUS_FAILURE, $startedAt);

        // newModelQuery() is the builder that carries neither $with nor
        // $withCount, so this is a crawler with no rollups attached.
        $bare = (new Crawler())->newModelQuery()->findOrFail($this->crawler->id);
        $this->assertArrayNotHasKey('latest_queues_degraded', $bare->getAttributes());

        DB::flushQueryLog();
        DB::enableQueryLog();

        $rollups = [
            $bare->latest_queues_succeeded,
            $bare->latest_queues_failed,
            $bare->latest_queues_warning,
            $bare->latest_queues_degraded,
            $bare->latest_queues_count,
        ];
        $queries = count(DB::getQueryLog());

        DB::disableQueryLog();

        // Neither run's own status_code is 'warning' -- the degraded one is a
        // success with a warning job -- so the fifth rollup is legitimately 0.
        $this->assertSame([1, 1, 0, 1, 2], $rollups);
        $this->assertSame(1, $queries, 'the fallback must fetch the set, not one query per attribute');
    }

    /**
     * The central bug this rollup exists for.
     *
     * The runner has reported queue-level 'warning' since 2026-08-28 (a run
     * that completed but lost jobs), and until latest_queues_warning existed
     * nothing counted it: succeeded and failed both exclude it, and
     * latest_queues_degraded is derived from the JOBS, so a run with no warning
     * job at all was invisible in every tile. The GUI, seeing a run total it
     * could not account for, showed the bot as "Running" indefinitely -- 73
     * queue rows on the production database were in exactly that state.
     *
     * @covers ::getLatestQueuesWarningAttribute
     * @covers ::queuesInWindowWarning
     */
    public function test_a_run_whose_own_status_is_warning_is_counted_once(): void
    {
        $startedAt = Carbon::now()->subHours(2)->format('Y-m-d H:i:s');

        $warned = $this->runWithStatus(Queue::STATUS_WARNING, $startedAt);
        // Deliberately no 'warning' job: the run itself gave up, every job it
        // did record finished or was skipped.
        $this->jobsFor($warned, [Queue::STATUS_SUCCESS, Queue::STATUS_FAILURE, Queue::STATUS_SKIPPED]);

        $this->assertSame(1, $this->crawler->latest_queues_warning);
        $this->assertSame(0, $this->crawler->latest_queues_succeeded, 'a warning run is not a success');
        $this->assertSame(0, $this->crawler->latest_queues_failed, 'a warning run is not a failure');
        $this->assertSame(0, $this->crawler->latest_queues_degraded, 'no job came back warning');
        $this->assertSame(1, $this->crawler->latest_queues_count);
    }

    /**
     * 'warn' is in the alias table because nothing normalises on write. A
     * reader that matches one spelling counts a fraction of the fleet.
     *
     * @covers ::getLatestQueuesWarningAttribute
     */
    public function test_the_legacy_warn_spelling_still_counts(): void
    {
        $startedAt = Carbon::now()->subHours(2)->format('Y-m-d H:i:s');

        $this->runWithStatus('warn', $startedAt);
        $this->runWithStatus(Queue::STATUS_WARNING, $startedAt);

        $this->assertSame(2, $this->crawler->latest_queues_warning);
    }

    /**
     * warning and degraded overlap and neither contains the other, which is why
     * the two counts must never be added together.
     *
     * @covers ::getLatestQueuesWarningAttribute
     * @covers ::getLatestQueuesDegradedAttribute
     */
    public function test_warning_and_degraded_are_different_questions(): void
    {
        $startedAt = Carbon::now()->subHours(2)->format('Y-m-d H:i:s');

        // The run gave up; every job it recorded is clean.
        $this->jobsFor($this->runWithStatus(Queue::STATUS_WARNING, $startedAt), [Queue::STATUS_SUCCESS]);
        // The run finished; one job silently lost its treatment.
        $this->jobsFor($this->runWithStatus(Queue::STATUS_SUCCESS, $startedAt), [Queue::STATUS_WARNING]);
        // Both at once.
        $this->jobsFor($this->runWithStatus(Queue::STATUS_WARNING, $startedAt), [Queue::STATUS_WARNING]);

        $this->assertSame(2, $this->crawler->latest_queues_warning);
        $this->assertSame(2, $this->crawler->latest_queues_degraded);
        $this->assertSame(1, $this->crawler->latest_queues_succeeded);
        $this->assertSame(3, $this->crawler->latest_queues_count);
    }

    /**
     * The window is a rolling 24 hours and the comparison is strict, so a run
     * that started exactly on the boundary is out.
     *
     * @covers ::queuesInWindowWarning
     * @covers ::latestQueuesSince
     */
    public function test_the_window_boundary_holds_for_the_warning_count(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-27 10:00:00'));

        $this->runWithStatus(Queue::STATUS_WARNING, '2026-08-26 14:00:00');  // 20h ago: in
        $this->runWithStatus(Queue::STATUS_WARNING, '2026-08-26 10:00:01');  // a second inside
        $this->runWithStatus(Queue::STATUS_WARNING, '2026-08-26 10:00:00');  // exactly 24h: out
        $this->runWithStatus(Queue::STATUS_WARNING, '2026-08-25 14:00:00');  // 44h ago: out

        $this->assertSame(2, $this->crawler->latest_queues_warning);
        $this->assertSame(2, $this->crawler->latest_queues_count);
    }

    /**
     * ?hours= moves the window for every rollup, the new one included.
     *
     * Each assertion re-reads the crawler, because a model answers a rollup
     * once and memoises it -- the window is a property of the query, not of the
     * row.
     *
     * @covers ::useLatestQueuesWindow
     * @covers ::latestQueuesSince
     */
    public function test_the_hours_window_widens_and_narrows_the_counts(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-27 10:00:00'));

        $this->runWithStatus(Queue::STATUS_WARNING, '2026-08-26 14:00:00');  // 20h ago
        $this->runWithStatus(Queue::STATUS_WARNING, '2026-08-25 14:00:00');  // 44h ago

        $this->assertSame(1, $this->reloadedCrawler()->latest_queues_warning, 'the default is 24h');

        Crawler::useLatestQueuesWindow(48);
        $this->assertSame(2, $this->reloadedCrawler()->latest_queues_warning);
        $this->assertSame(2, $this->reloadedCrawler()->latest_queues_count);

        Crawler::useLatestQueuesWindow(2);
        $this->assertSame(0, $this->reloadedCrawler()->latest_queues_warning);

        Crawler::resetLatestQueuesWindow();
        $this->assertSame(1, $this->reloadedCrawler()->latest_queues_warning);
    }

    /**
     * A dashboard asking for a nonsense window gets the nearest sane one, not a
     * 400: the request is for a picture of the fleet, and refusing to draw it
     * because someone typed 0 helps nobody.
     *
     * @covers ::useLatestQueuesWindow
     */
    public function test_the_hours_window_is_clamped_rather_than_rejected(): void
    {
        $this->assertSame(Crawler::LATEST_QUEUES_HOURS_MAX, Crawler::useLatestQueuesWindow(100000));
        $this->assertSame(Crawler::LATEST_QUEUES_HOURS_MIN, Crawler::useLatestQueuesWindow(0));
        $this->assertSame(Crawler::LATEST_QUEUES_HOURS_MIN, Crawler::useLatestQueuesWindow(-5));
        $this->assertSame(48, Crawler::useLatestQueuesWindow('48'));
        // Anything unusable means "no preference", which is the 24h default.
        $this->assertSame(Crawler::LATEST_QUEUES_HOURS, Crawler::useLatestQueuesWindow('last week'));
        $this->assertSame(Crawler::LATEST_QUEUES_HOURS, Crawler::useLatestQueuesWindow(null));
        $this->assertSame(Crawler::LATEST_QUEUES_HOURS, Crawler::useLatestQueuesWindow(['48']));
    }

    /**
     * ?hours= over HTTP, on both endpoints that serialise a crawler.
     *
     * show() is the interesting half: route-model binding has already run the
     * subselects over the default window before the controller sees the query
     * string, so without forgetLatestQueueCounts() the parameter would be
     * silently ignored on exactly the page a researcher opens to look closer.
     *
     * @covers \App\Modules\Crawler\Http\Controller\Api\v1\CrawlerController::index
     * @covers \App\Modules\Crawler\Http\Controller\Api\v1\CrawlerController::show
     */
    public function test_the_endpoints_accept_an_hours_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-27 10:00:00'));

        $this->runWithStatus(Queue::STATUS_WARNING, '2026-08-26 14:00:00');  // 20h ago
        $this->runWithStatus(Queue::STATUS_WARNING, '2026-08-25 14:00:00');  // 44h ago

        $this->assertSame(1, $this->warningCountFromIndex());
        $this->assertSame(2, $this->warningCountFromIndex('?hours=48'));
        $this->assertSame(0, $this->warningCountFromIndex('?hours=2'));
        // Out of range: clamped to 720, which still contains both runs.
        $this->assertSame(2, $this->warningCountFromIndex('?hours=100000'));

        $show = '/api/v1/crawlers/'.$this->crawler->id;
        $this->assertSame(
            1,
            $this->actingAs($this->user)->getJson($show)->assertOk()->json('data.latest_queues_warning')
        );
        $this->assertSame(
            2,
            $this->actingAs($this->user)->getJson($show.'?hours=48')->assertOk()->json('data.latest_queues_warning')
        );
    }

    /**
     * The fifth rollup has to stay a count subselect on the loading query.
     *
     * As an appended accessor it would cost one query per crawler row -- the
     * regression the other four were rewritten to remove -- and this page is
     * the one the workspace loads.
     *
     * @covers ::queuesInWindowWarning
     */
    public function test_the_warning_rollup_does_not_fan_out_into_a_query_per_row(): void
    {
        $this->crawlersWithWarningRuns(50);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $page = app(CrawlerRepository::class)->all(50);
        $serialised = $page->toArray();
        $queries = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertCount(50, $page->items());
        $this->assertLessThanOrEqual(
            10,
            $queries,
            "a 50-row crawler page cost {$queries} queries; a per-row accessor would add 50"
        );

        $row = $serialised['data'][0];
        $this->assertArrayHasKey('latest_queues_warning', $row);
        $this->assertSame(1, $row['latest_queues_warning']);
        $this->assertSame(0, $row['latest_queues_succeeded']);
    }

    /**
     * The rollup that is about the SCIENCE rather than the machinery.
     *
     * A run that lost its treatment normally looks perfect from every other
     * column: status 'success', every job 'success', a real video clicked, the
     * session finished. Nothing FAILED -- the data simply belongs to the
     * control arm instead of the treatment arm it was assigned to. No count
     * before this one could see it.
     *
     * @covers ::getLatestQueuesTreatmentLostAttribute
     * @covers ::queuesInWindowTreatmentLost
     */
    public function test_a_run_that_lost_its_treatment_is_counted_while_nothing_failed(): void
    {
        $startedAt = Carbon::now()->subHours(2)->format('Y-m-d H:i:s');

        $lost = $this->runWithStatus(Queue::STATUS_SUCCESS, $startedAt);
        $this->jobsFor($lost, [Queue::STATUS_SUCCESS, Queue::STATUS_SUCCESS]);
        $lost->jobs()->first()->forceFill([
            'decision_degraded' => true,
            'decision_treatment_lost' => true,
        ])->save();

        $clean = $this->runWithStatus(Queue::STATUS_SUCCESS, $startedAt);
        $this->jobsFor($clean, [Queue::STATUS_SUCCESS]);

        $this->assertSame(1, $this->crawler->latest_queues_treatment_lost);
        $this->assertSame(2, $this->crawler->latest_queues_succeeded, 'both runs succeeded, by their own status');
        $this->assertSame(0, $this->crawler->latest_queues_failed);
        $this->assertSame(0, $this->crawler->latest_queues_warning);
        $this->assertSame(0, $this->crawler->latest_queues_degraded, 'no job came back warning either');
    }

    /**
     * It counts runs, not jobs: a session that lost the treatment on every one
     * of its selects is one void session. The per-job number is the queue's
     * jobs_treatment_lost_count.
     *
     * @covers ::getLatestQueuesTreatmentLostAttribute
     */
    public function test_a_run_with_several_lost_treatments_counts_once(): void
    {
        $run = $this->runWithStatus(Queue::STATUS_SUCCESS, Carbon::now()->subHours(2)->format('Y-m-d H:i:s'));
        $this->jobsFor($run, [Queue::STATUS_SUCCESS, Queue::STATUS_SUCCESS, Queue::STATUS_SUCCESS]);

        foreach ($run->jobs()->get() as $job) {
            $job->forceFill(['decision_treatment_lost' => true])->save();
        }

        $this->assertSame(1, $this->crawler->latest_queues_treatment_lost);
        $this->assertSame(1, $this->crawler->latest_queues_count);
    }

    /**
     * NULL is unknown, and unknown is not an alarm. Every job that predates the
     * runner deploy, and every job that never called the decision function, is
     * NULL -- so a zero here means "nothing in the window is KNOWN to have lost
     * its treatment", which is weaker than it looks and must not be rendered as
     * a clean bill of health.
     *
     * @covers ::getLatestQueuesTreatmentLostAttribute
     */
    public function test_null_and_false_verdicts_do_not_count(): void
    {
        $startedAt = Carbon::now()->subHours(2)->format('Y-m-d H:i:s');

        // A run full of jobs nobody recorded a verdict for.
        $unknown = $this->runWithStatus(Queue::STATUS_SUCCESS, $startedAt);
        $this->jobsFor($unknown, [Queue::STATUS_SUCCESS, Queue::STATUS_SUCCESS]);

        // A run whose jobs were all measured and were all fine.
        $measured = $this->runWithStatus(Queue::STATUS_SUCCESS, $startedAt);
        $this->jobsFor($measured, [Queue::STATUS_SUCCESS]);
        $measured->jobs()->first()->forceFill([
            'decision_degraded' => false,
            'decision_treatment_lost' => false,
        ])->save();

        $this->assertSame(0, $this->crawler->latest_queues_treatment_lost);
        $this->assertSame(2, $this->crawler->latest_queues_count);
    }

    /**
     * @covers ::queuesInWindowTreatmentLost
     */
    public function test_a_lost_treatment_outside_the_window_does_not_count(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-27 10:00:00'));

        $old = $this->runWithStatus(Queue::STATUS_SUCCESS, '2026-08-25 14:00:00');
        $this->jobsFor($old, [Queue::STATUS_SUCCESS]);
        $old->jobs()->first()->forceFill(['decision_treatment_lost' => true])->save();

        $this->assertSame(0, $this->crawler->latest_queues_treatment_lost);
    }

    /**
     * The sixth rollup has to stay a count subselect on the loading query, and
     * the fallback has to keep fetching the whole set in one aggregate.
     *
     * @covers ::queuesInWindowTreatmentLost
     */
    public function test_the_treatment_lost_rollup_costs_nothing_extra(): void
    {
        $startedAt = Carbon::now()->subHours(2)->format('Y-m-d H:i:s');
        $run = $this->runWithStatus(Queue::STATUS_SUCCESS, $startedAt);
        $this->jobsFor($run, [Queue::STATUS_SUCCESS]);
        $run->jobs()->first()->forceFill(['decision_treatment_lost' => true])->save();

        $bare = (new Crawler())->newModelQuery()->findOrFail($this->crawler->id);
        $this->assertArrayNotHasKey('latest_queues_treatment_lost', $bare->getAttributes());

        DB::flushQueryLog();
        DB::enableQueryLog();

        $rollups = [
            $bare->latest_queues_succeeded,
            $bare->latest_queues_failed,
            $bare->latest_queues_warning,
            $bare->latest_queues_degraded,
            $bare->latest_queues_treatment_lost,
            $bare->latest_queues_count,
        ];
        $queries = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertSame([1, 0, 0, 0, 1, 1], $rollups);
        $this->assertSame(1, $queries, 'the fallback must fetch the set, not one query per attribute');

        // ...and on the loading query it is a subselect like its five siblings.
        $row = Crawler::query()->findOrFail($this->crawler->id)->toArray();
        $this->assertSame(1, $row['latest_queues_treatment_lost']);
    }

    private function warningCountFromIndex(string $query = ''): int
    {
        $rows = $this->actingAs($this->user)
            ->getJson('/api/v1/crawlers'.$query)
            ->assertOk()
            ->json('data');

        foreach ($rows as $row) {
            if ($this->crawler->id === $row['id']) {
                return $row['latest_queues_warning'];
            }
        }

        $this->fail('the crawler under test was not on the /crawlers page');
    }

    /**
     * The crawler as a fresh query would load it, rollups included. Reading
     * $this->crawler twice would answer from its memoised counts instead.
     */
    private function reloadedCrawler(): Crawler
    {
        return Crawler::query()->findOrFail($this->crawler->id);
    }

    private function crawlersWithWarningRuns(int $count): void
    {
        $startedAt = Carbon::now()->subHours(2)->format('Y-m-d H:i:s');

        for ($i = 0; $i < $count; $i++) {
            $crawler = Crawler::factory()->create([
                'proxy_id' => $this->proxy->id,
                'agent_id' => $this->agent->id,
                'experiment_id' => $this->experiment->id,
                'configuration_id' => $this->configuration->id,
                'active' => 1,
                'testing' => 0,
            ]);

            Queue::factory()->create([
                'crawler_id' => $crawler->id,
                'status_code' => Queue::STATUS_WARNING,
                'started_at' => $startedAt,
            ]);
        }
    }

    /**
     * @param string[] $statuses
     */
    private function jobsFor(Queue $queue, array $statuses): void
    {
        foreach ($statuses as $status) {
            Job::factory()->create(['queue_id' => $queue->id, 'status_code' => $status]);
        }
    }

    private function crawlersWithRuns(int $count): void
    {
        $startedAt = Carbon::now()->subHours(2)->format('Y-m-d H:i:s');

        for ($i = 0; $i < $count; $i++) {
            $crawler = Crawler::factory()->create([
                'proxy_id' => $this->proxy->id,
                'agent_id' => $this->agent->id,
                'experiment_id' => $this->experiment->id,
                'configuration_id' => $this->configuration->id,
                'active' => 1,
                'testing' => 0,
            ]);

            $run = Queue::factory()->create([
                'crawler_id' => $crawler->id,
                'status_code' => Queue::STATUS_SUCCESS,
                'started_at' => $startedAt,
            ]);

            Job::factory()->create(['queue_id' => $run->id, 'status_code' => Queue::STATUS_WARNING]);
        }
    }

    private function runWithStatus(string $status, string $startedAt): Queue
    {
        return Queue::factory()->create([
            'crawler_id' => $this->crawler->id,
            'status_code' => $status,
            'started_at' => $startedAt,
        ]);
    }
}
