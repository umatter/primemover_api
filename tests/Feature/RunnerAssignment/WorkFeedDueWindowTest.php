<?php

namespace Tests\Feature\RunnerAssignment;

use App\Modules\Crawler\Model\Crawler;
use App\Modules\Crawler\Repository\CrawlerRepository;
use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * GET /crawlers-unprocessed hands the runner the work that is due, not every
 * queue it will ever run.
 *
 * On 2026-10-06/07 the feed carried every pending queue of every experiment --
 * about 11,084 queues and 394k jobs per poll -- and answered 500 for about
 * sixteen hours, so the runner on ex63 started nothing for anyone. These tests
 * pin the window that replaced it, and the parts of today's behaviour the
 * runner depends on and the window must not lose: overdue rows (the runner is
 * the only thing that closes them), processing rows (the 180-minute timeout
 * only fires on rows it receives), reviewed rows (closed on sight), and the
 * per-runner partition.
 *
 * The clock is frozen at 2026-10-09 06:00 Zurich, so with the configured 30 and
 * 60 minutes due_by is 06:30:00 and stale_before is 05:00:00 on that day.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Crawler\Repository\CrawlerRepository
 */
class WorkFeedDueWindowTest extends RunnerAssignmentTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->enableIsolation();
        config([
            'primemover.runner.lookahead_minutes' => 30,
            'primemover.runner.stale_after_minutes' => 60,
            'primemover.runner.max_queues_per_poll' => 300,
            'primemover.runner.max_jobs_per_poll' => 10000,
        ]);
        Carbon::setTestNow(Carbon::parse('2026-10-09 06:00:00', 'Europe/Zurich'));
        $this->actingAs($this->defaultRunner);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @covers ::unprocessed
     */
    public function test_the_window_ends_at_now_plus_the_lookahead_inclusive(): void
    {
        $inside = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 06:10:00']);
        $atTheEdge = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 06:30:00']);
        $aSecondLate = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 06:30:01']);
        $later = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 06:31:00']);

        $ids = $this->feedQueueIds();

        $this->assertContains((int) $inside->id, $ids);
        $this->assertContains((int) $atTheEdge->id, $ids);
        $this->assertNotContains((int) $aSecondLate->id, $ids);
        $this->assertNotContains((int) $later->id, $ids);
    }

    /**
     * @covers ::unprocessed
     */
    public function test_the_lookahead_follows_config_with_a_floor(): void
    {
        $at0645 = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 06:45:00']);
        $at070001 = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 07:00:01']);
        $at0614 = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 06:14:00']);
        $at0616 = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 06:16:00']);

        config(['primemover.runner.lookahead_minutes' => 60]);
        $ids = $this->feedQueueIds();
        $this->assertContains((int) $at0645->id, $ids);
        $this->assertNotContains((int) $at070001->id, $ids);

        // Below the runner's own code-default poll interval: floored to 15.
        config(['primemover.runner.lookahead_minutes' => 5]);
        $ids = $this->feedQueueIds();
        $this->assertContains((int) $at0614->id, $ids);
        $this->assertNotContains((int) $at0616->id, $ids);
    }

    /**
     * A config cache built before the four keys existed must not shrink the
     * window to its floor, or the cap to 1: every missing key means its default.
     *
     * @covers ::unprocessed
     */
    public function test_missing_settings_fall_back_to_the_defaults(): void
    {
        config(['primemover.runner' => ['default_user_id' => $this->defaultRunner->id]]);

        $due = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 06:20:00']);
        $overdue = [
            $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 05:59:00']),
            $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 05:00:00']),
            $this->queueOn($this->forDefault, ['start_at' => '2026-10-01 06:00:00']),
        ];

        $ids = $this->feedQueueIds();

        $this->assertContains((int) $due->id, $ids, 'the 30-minute default lookahead must apply');
        foreach ($overdue as $queue) {
            $this->assertContains((int) $queue->id, $ids, 'the default caps must not cut three rows');
        }
    }

    /**
     * No lower bound: the runner is the only thing that closes a stale queue
     * (SKIPPED_TIMEDOUT), and it can only close what it receives.
     *
     * @covers ::unprocessed
     */
    public function test_overdue_rows_are_sent_however_late(): void
    {
        $queues = [
            $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 05:59:00']),
            $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 05:00:00']),
            $this->queueOn($this->forDefault, ['start_at' => '2026-10-01 06:00:00']),
        ];

        $ids = $this->feedQueueIds();

        foreach ($queues as $queue) {
            $this->assertContains((int) $queue->id, $ids);
        }
    }

    /**
     * The runner's 180-minute processing timeout fires only on rows it receives.
     *
     * @covers ::unprocessed
     */
    public function test_processing_rows_are_sent_whatever_their_start(): void
    {
        $longRunning = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 01:00:00', 'processing' => 1]);
        $editedForward = $this->queueOn($this->forDefault, ['start_at' => '2026-10-12 06:00:00', 'processing' => 1]);

        $ids = $this->feedQueueIds();

        $this->assertContains((int) $longRunning->id, $ids);
        $this->assertContains((int) $editedForward->id, $ids);
    }

    /**
     * The runner closes a reviewed queue on sight (SKIPPED_REVIEWED), whatever
     * its start_at.
     *
     * @covers ::unprocessed
     */
    public function test_reviewed_rows_are_sent_before_they_are_due(): void
    {
        $reviewed = $this->queueOn($this->forDefault, ['start_at' => '2026-10-12 06:00:00', 'reviewed' => 1]);

        $this->assertContains((int) $reviewed->id, $this->feedQueueIds());
    }

    /**
     * @covers ::unprocessed
     */
    public function test_processed_and_inactive_rows_stay_out(): void
    {
        $processed = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 06:10:00', 'processed' => 1]);
        $inactive = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 06:10:00', 'active' => 0]);

        $ids = $this->feedQueueIds();

        $this->assertNotContains((int) $processed->id, $ids);
        $this->assertNotContains((int) $inactive->id, $ids);
    }

    /**
     * NULL, '' and any string that is not the canonical 'Y-m-d H:i:s' compare
     * wrongly as text, so they count as due -- today's behaviour. The runner
     * parses the shapes it can and leaves them until they are due.
     *
     * @covers ::unprocessed
     */
    public function test_null_empty_and_non_canonical_start_at_are_sent_as_due(): void
    {
        $null = $this->queueOn($this->forDefault, ['start_at' => null]);
        $empty = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 06:10:00']);
        $isoT = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 06:10:00']);
        $unpadded = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 06:10:00']);

        // The mutator would normalise these, so write the raw strings.
        DB::table('queues')->where('id', $empty->id)->update(['start_at' => '']);
        DB::table('queues')->where('id', $isoT->id)->update(['start_at' => '2026-10-09T06:10:00']);
        DB::table('queues')->where('id', $unpadded->id)->update(['start_at' => '2026-10-09 6:10:00']);

        $ids = $this->feedQueueIds();

        foreach ([$null, $empty, $isoT, $unpadded] as $queue) {
            $this->assertContains((int) $queue->id, $ids);
        }
    }

    /**
     * Most overdue first, ties by id, stale rows last.
     *
     * @covers ::unprocessed
     */
    public function test_queues_within_a_crawler_come_in_priority_order(): void
    {
        $this->onlyMine();

        $at0620 = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 06:20:00']);
        $at0530 = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 05:30:00']);
        $at0610a = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 06:10:00']);
        $at0610b = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 06:10:00']);
        $stale = $this->queueOn($this->forDefault, ['start_at' => '2026-10-01 06:00:00']);

        $this->assertSame(
            [(int) $at0530->id, (int) $at0610a->id, (int) $at0610b->id, (int) $at0620->id, (int) $stale->id],
            $this->feedQueueIds()
        );

        // Re-sorted, the relation must be re-keyed: a list with gaps in its keys
        // json_encodes as an object. Asserted here because the resource layer
        // happens to re-index on the way out and would hide it over HTTP.
        $crawler = (new CrawlerRepository(new Crawler()))->unprocessed()->first();
        $this->assertSame(range(0, 4), $crawler->queues->keys()->all());
    }

    /**
     * The runner's executor is FIFO, so the crawler holding the most urgent
     * queue goes first, whatever the ids say. data must stay a JSON list, and
     * the repository's own result stays keyed 0..n after the re-sort.
     */
    public function test_crawlers_come_in_priority_order_and_data_stays_a_list(): void
    {
        $this->onlyMine();
        $this->assertLessThan((int) $this->forDefault['crawler']->id, (int) $this->unassigned['crawler']->id);

        // The higher crawler id holds the more urgent queue.
        $this->queueOn($this->unassigned, ['start_at' => '2026-10-09 05:40:00']);
        $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 05:20:00']);

        $response = $this->getJson('/api/v1/crawlers-unprocessed')->assertOk();

        $this->assertSame(
            [(int) $this->forDefault['crawler']->id, (int) $this->unassigned['crawler']->id],
            array_map('intval', array_column($response->json('data'), 'id'))
        );
        $this->assertStringStartsWith('{"data":[', $response->getContent());
        $this->assertSame([0, 1], (new CrawlerRepository(new Crawler()))->unprocessed()->keys()->all());
    }

    /**
     * @covers ::unprocessed
     */
    public function test_crawlers_with_only_future_work_are_left_out(): void
    {
        $this->forSecond['queue']->update(['start_at' => '2026-10-10 06:00:00']);

        $this->actingAs($this->secondRunner);
        $result = (new CrawlerRepository(new Crawler()))->unprocessed();
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);

        $response = $this->getJson('/api/v1/crawlers-unprocessed')->assertOk();
        $this->assertSame([], $response->json('data'));
        $this->assertStringStartsWith('{"data":[]', $response->getContent());

        $this->forDefault['queue']->update(['start_at' => '2026-10-10 06:00:00']);
        $this->actingAs($this->defaultRunner);
        $this->assertNotContains((int) $this->forDefault['crawler']->id, $this->feedCrawlerIds());
    }

    /**
     * The cap is applied per runner, after the partition: another runner's
     * more urgent work must neither leak in nor use up this runner's slots.
     *
     * @covers ::unprocessed
     */
    public function test_the_partition_holds_under_the_cap(): void
    {
        Log::spy();
        $this->onlyMine();
        config(['primemover.runner.max_queues_per_poll' => 1]);

        $second = $this->queueOn($this->forSecond, ['start_at' => '2026-10-09 05:10:00']);
        $unassigned = $this->queueOn($this->unassigned, ['start_at' => '2026-10-09 05:30:00']);
        $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 05:40:00']);

        $this->actingAs($this->secondRunner);
        $this->assertSame([(int) $second->id], $this->feedQueueIds());

        $this->actingAs($this->defaultRunner);
        $this->assertSame([(int) $unassigned->id], $this->feedQueueIds());
    }

    /**
     * @covers ::unprocessed
     */
    public function test_the_queue_cap_sends_the_most_urgent_and_logs_runnable_work_held_back(): void
    {
        Log::spy();
        $this->onlyMine();
        config(['primemover.runner.max_queues_per_poll' => 2]);

        $at0510 = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 05:10:00']);
        $at0530 = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 05:30:00']);
        $at0550 = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 05:50:00']);
        $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 07:00:00']);

        $this->assertSame([(int) $at0510->id, (int) $at0530->id], $this->feedQueueIds());

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => str_contains($message, 'crawlers-unprocessed held work back')
                && 3 === $context['due']
                && 2 === $context['sent']
                && 1 === $context['held_back']
                && 'queues' === $context['bound_by']
                && 1 === $context['runnable_held_back']
                && (int) $at0550->id === $context['first_held_back_queue_id']
                && '2026-10-09 05:50:00' === $context['first_held_back_start_at']
                && (int) $this->defaultRunner->id === $context['runner_user_id']
                && '2026-10-09 06:30:00' === $context['due_by']);
        Log::shouldNotHaveReceived('warning');
    }

    /**
     * Rows the runner can only skip must never take the place of rows it can
     * still run.
     *
     * @covers ::unprocessed
     */
    public function test_runnable_work_outranks_stale_work_under_the_cap(): void
    {
        Log::spy();
        $this->onlyMine();
        config(['primemover.runner.max_queues_per_poll' => 2]);

        $stale = [
            $this->queueOn($this->forDefault, ['start_at' => '2026-10-01 06:00:00']),
            $this->queueOn($this->forDefault, ['start_at' => '2026-10-01 06:00:00']),
            $this->queueOn($this->forDefault, ['start_at' => '2026-10-01 06:00:00']),
        ];
        $runnable = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 05:59:00']);

        $ids = $this->feedQueueIds();

        $this->assertSame([(int) $runnable->id, (int) $stale[0]->id], $ids);
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => 2 === $context['stale_held_back']
                && 0 === $context['runnable_held_back']
                && 1 === $context['runnable_sent']
                && 1 === $context['stale_sent']);
        Log::shouldNotHaveReceived('error');
    }

    /**
     * @covers ::unprocessed
     */
    public function test_a_null_start_at_does_not_outrank_due_work(): void
    {
        Log::spy();
        $this->onlyMine();
        config(['primemover.runner.max_queues_per_poll' => 1]);

        $this->queueOn($this->forDefault, ['start_at' => null]);
        $due = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 05:59:00']);

        $this->assertSame([(int) $due->id], $this->feedQueueIds());
    }

    /**
     * Memory scales with jobs, not queues: the job budget is the guard. The
     * first row is always sent, so a single oversized queue cannot stall the
     * feed.
     *
     * @covers ::unprocessed
     */
    public function test_the_job_budget_bites_before_the_queue_cap(): void
    {
        Log::spy();
        $this->onlyMine();
        config(['primemover.runner.max_jobs_per_poll' => 100]);

        $at0510 = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 05:10:00'], 60);
        $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 05:20:00'], 60);
        $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 05:30:00'], 60);

        $this->assertSame([(int) $at0510->id], $this->feedQueueIds());
        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => 'jobs' === $context['bound_by']
                && 60 === $context['jobs_sent']
                && 2 === $context['held_back']);

        config(['primemover.runner.max_jobs_per_poll' => 50]);
        $this->assertSame([(int) $at0510->id], $this->feedQueueIds(), 'the first row is always sent');
    }

    /**
     * @covers ::unprocessed
     */
    public function test_processing_rows_are_never_cut(): void
    {
        Log::spy();
        $this->onlyMine();
        config(['primemover.runner.max_queues_per_poll' => 1]);

        $processing = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 05:58:00', 'processing' => 1]);
        $at0510 = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 05:10:00']);
        $at0520 = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 05:20:00']);

        $ids = $this->feedQueueIds();

        $this->assertContains((int) $processing->id, $ids);
        $this->assertContains((int) $at0510->id, $ids);
        $this->assertNotContains((int) $at0520->id, $ids);
        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => 1 === $context['held_back']
                && 1 === $context['processing_sent']);
    }

    /**
     * @covers ::unprocessed
     */
    public function test_nothing_is_logged_under_the_cap(): void
    {
        Log::spy();
        $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 05:10:00'], 3);
        $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 06:10:00'], 3);
        $this->queueOn($this->forDefault, ['start_at' => '2026-10-01 06:00:00']);

        $this->feedQueueIds();

        Log::shouldNotHaveReceived('error');
        Log::shouldNotHaveReceived('warning');
    }

    /**
     * 2026-10-25, the night Zurich leaves summer time: at 00:40Z the wall clock
     * reads 02:40 CEST, and 30 minutes later it reads 02:10 CET. The bound has
     * to come from UTC arithmetic, then be formatted.
     *
     * @covers ::unprocessed
     */
    public function test_the_window_is_computed_across_the_dst_change(): void
    {
        $this->assertSame('Europe/Zurich', config('app.timezone'));
        Carbon::setTestNow(Carbon::parse('2026-10-25T00:40:00Z'));

        $due = $this->queueOn($this->forDefault, ['start_at' => '2026-10-25 02:05:00']);
        $notYet = $this->queueOn($this->forDefault, ['start_at' => '2026-10-25 03:05:00']);

        $ids = $this->feedQueueIds();

        $this->assertContains((int) $due->id, $ids);
        $this->assertNotContains((int) $notYet->id, $ids);
    }

    /**
     * What the Java runner deserialises: ISO-8601 with an offset, a jobs array,
     * and the four flags it unboxes.
     */
    public function test_the_response_shape_is_unchanged(): void
    {
        $this->onlyMine();

        $due = $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 06:05:00'], 1);
        $future = $this->queueOn($this->forDefault, ['start_at' => '2026-10-10 06:05:00']);
        // More urgent but a higher id, so the priority sort reorders the relation.
        $this->queueOn($this->forDefault, ['start_at' => '2026-10-09 05:05:00']);

        $data = $this->getJson('/api/v1/crawlers-unprocessed')->assertOk()->json('data');
        foreach ($data as $crawler) {
            $this->assertTrue(array_is_list($crawler['queues']), 'queues must serialise as a JSON list');
        }
        $queues = array_merge(...array_map(fn (array $crawler): array => $crawler['queues'], $data));
        $byId = array_column($queues, null, 'id');

        $this->assertArrayHasKey($due->id, $byId);
        $this->assertArrayNotHasKey($future->id, $byId);

        $queue = $byId[$due->id];
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $queue['start_at']);
        $this->assertIsArray($queue['jobs']);
        $this->assertNotEmpty($queue['jobs']);
        foreach (['processed', 'processing', 'active', 'reviewed'] as $flag) {
            $this->assertArrayHasKey($flag, $queue);
        }
    }

    /**
     * Non-production runners rewrite every start_at to today, so a date window
     * on crawlers-testing would hide test work.
     *
     * @covers ::testing
     */
    public function test_the_testing_feed_is_not_windowed(): void
    {
        $this->forSecond['crawler']->update(['testing' => 1]);
        $this->forSecond['queue']->update(['start_at' => '2026-10-12 06:00:00']);

        $this->actingAs($this->secondRunner);
        $crawlers = (new CrawlerRepository(new Crawler()))->testing()->items();

        $this->assertContains((int) $this->forSecond['crawler']->id, $this->idsOf($crawlers));
        $crawler = collect($crawlers)->first(fn ($c): bool => (int) $c->id === (int) $this->forSecond['crawler']->id);
        $this->assertContains((int) $this->forSecond['queue']->id, $crawler->queues->pluck('id')->map(fn ($id): int => (int) $id)->all());
    }

    /**
     * A queue on $tree's crawler with every flag pinned (the factory randomises
     * them) and $jobs active jobs.
     *
     * @param array<string, Model> $tree
     * @param array<string, mixed> $attributes
     */
    private function queueOn(array $tree, array $attributes, int $jobs = 0): Queue
    {
        $queue = Queue::factory()->create(array_merge([
            'user_id' => $this->serviceAccount->id,
            'crawler_id' => $tree['crawler']->id,
            'active' => 1,
            'new' => 1,
            'processed' => 0,
            'processing' => 0,
            'reviewed' => 0,
            'status_code' => null,
            'started_at' => null,
        ], $attributes));

        if ($jobs > 0) {
            Job::factory()->count($jobs)->create([
                'user_id' => $this->serviceAccount->id,
                'queue_id' => $queue->id,
                'active' => 1,
            ]);

            // JobFactory::definition() creates a throwaway queue per job before
            // the override applies, with random flags and a NULL start_at --
            // i.e. due. Drop them, or they enter the feed under test.
            DB::table('queues')->where('id', '>', $queue->id)->delete();
        }

        return $queue;
    }

    /** @return array<int, int> queue ids in feed order */
    private function feedQueueIds(): array
    {
        return (new CrawlerRepository(new Crawler()))->unprocessed()
            ->flatMap(fn (Crawler $crawler) => $crawler->queues->pluck('id'))
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    /** @return array<int, int> crawler ids in feed order */
    private function feedCrawlerIds(): array
    {
        return (new CrawlerRepository(new Crawler()))->unprocessed()
            ->map(fn (Crawler $crawler): int => (int) $crawler->id)
            ->values()
            ->all();
    }

    /**
     * Take every fixture queue out of the feed. Their start_at is NULL, which
     * the feed counts as due, so order and cap tests would see them.
     */
    private function onlyMine(): void
    {
        DB::table('queues')->update(['processed' => 1]);
    }
}
