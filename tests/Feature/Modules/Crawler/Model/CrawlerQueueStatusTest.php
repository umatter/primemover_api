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
     * has to answer, and must fetch all four rollups in one aggregate rather
     * than one query each.
     *
     * @covers ::getLatestQueuesDegradedAttribute
     */
    public function test_a_crawler_loaded_without_the_subselects_costs_one_query_for_all_four(): void
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
            $bare->latest_queues_degraded,
            $bare->latest_queues_count,
        ];
        $queries = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertSame([1, 1, 1, 2], $rollups);
        $this->assertSame(1, $queries, 'the fallback must fetch the set, not one query per attribute');
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
