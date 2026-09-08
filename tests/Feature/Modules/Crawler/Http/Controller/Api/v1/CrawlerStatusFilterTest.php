<?php

namespace Tests\Feature\Modules\Crawler\Http\Controller\Api\v1;

use App\Modules\Crawler\Model\Crawler;
use App\Modules\Crawler\Repository\CrawlerRepository;
use App\Modules\Queue\Model\Queue;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * filter[status_code] on GET /crawlers -- "which bots need attention?"
 *
 * A crawler has no status of its own, so the filter is applied to its LATEST
 * RUN. The definition matters more than it looks: this API composes runs ahead
 * of time, so a bot routinely owns queues with a future start_at and no
 * status_code at all, and letting one of those count as "latest" would hide the
 * failure that happened last night behind a run that has not happened yet.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Crawler\Model\Crawler
 */
class CrawlerStatusFilterTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @covers ::scopeLatestRunStatusIn
     */
    public function test_it_selects_bots_whose_latest_run_failed(): void
    {
        $broken = $this->bot();
        $this->runFor($broken, Queue::STATUS_FAILURE, '2026-09-06 10:00:00');

        $fine = $this->bot();
        $this->runFor($fine, Queue::STATUS_SUCCESS, '2026-09-06 10:00:00');

        $this->assertSame([$broken->id], $this->idsFrom('filter[status_code]=failure'));
    }

    /**
     * The bot that failed this morning and has succeeded twice since is not the
     * bot to look at -- only the LATEST run counts.
     *
     * @covers ::scopeLatestRunStatusIn
     */
    public function test_an_earlier_failure_does_not_select_a_bot_that_has_recovered(): void
    {
        $recovered = $this->bot();
        $this->runFor($recovered, Queue::STATUS_FAILURE, '2026-09-06 08:00:00');
        $this->runFor($recovered, Queue::STATUS_SUCCESS, '2026-09-06 09:00:00');

        $stillBroken = $this->bot();
        $this->runFor($stillBroken, Queue::STATUS_SUCCESS, '2026-09-06 08:00:00');
        $this->runFor($stillBroken, Queue::STATUS_FAILURE, '2026-09-06 09:00:00');

        $this->assertSame([$stillBroken->id], $this->idsFrom('filter[status_code]=failure'));
        $this->assertSame([$recovered->id], $this->idsFrom('filter[status_code]=success'));
    }

    /**
     * A run that has not started has no outcome, so it cannot be the latest
     * one. Ranking by id instead of by started_at would let a run composed for
     * tomorrow mask the failure from last night -- and composing ahead is how
     * this API is used.
     *
     * @covers ::scopeLatestRunStatusIn
     */
    public function test_a_run_scheduled_for_later_does_not_mask_the_last_real_outcome(): void
    {
        $broken = $this->bot();
        $this->runFor($broken, Queue::STATUS_FAILURE, '2026-09-06 09:00:00');
        // Composed afterwards, higher id, never started, no status yet.
        Queue::factory()->create([
            'crawler_id' => $broken->id,
            'status_code' => null,
            'started_at' => null,
            'start_at' => '2026-09-08 09:00:00',
        ]);

        $this->assertSame([$broken->id], $this->idsFrom('filter[status_code]=failure'));
    }

    /**
     * The alias table applies here as everywhere: 277 queue rows spell it
     * 'failed'.
     *
     * @covers ::scopeLatestRunStatusIn
     */
    public function test_the_legacy_spelling_of_the_latest_run_still_matches(): void
    {
        $legacy = $this->bot();
        $this->runFor($legacy, 'failed', '2026-09-06 10:00:00');

        $this->assertSame([$legacy->id], $this->idsFrom('filter[status_code]=failure'));
    }

    /**
     * failure,warning over bots is the is_bad predicate one level up: which of
     * my bots ended their last session badly.
     *
     * @covers ::scopeLatestRunStatusIn
     */
    public function test_a_comma_list_asks_which_bots_need_attention(): void
    {
        $failed = $this->bot();
        $this->runFor($failed, Queue::STATUS_FAILURE, '2026-09-06 10:00:00');

        $warned = $this->bot();
        $this->runFor($warned, 'warn', '2026-09-06 10:00:00');

        $ok = $this->bot();
        $this->runFor($ok, Queue::STATUS_SUCCESS, '2026-09-06 10:00:00');

        $skipped = $this->bot();
        $this->runFor($skipped, 'skpped', '2026-09-06 10:00:00');

        $this->assertEqualsCanonicalizing(
            [$failed->id, $warned->id],
            $this->idsFrom('filter[status_code]=failure,warning')
        );
    }

    /**
     * A bot that has never run has no latest run and therefore matches no
     * status. It is not quietly filed under success.
     *
     * @covers ::scopeLatestRunStatusIn
     */
    public function test_a_bot_that_has_never_run_matches_no_status(): void
    {
        $this->bot();

        $this->assertSame([], $this->idsFrom('filter[status_code]=success'));
        $this->assertSame([], $this->idsFrom('filter[status_code]=failure'));
    }

    /**
     * @covers ::scopeLatestRunStatusIn
     */
    public function test_an_unrecognised_status_value_returns_an_empty_page(): void
    {
        $bot = $this->bot();
        $this->runFor($bot, Queue::STATUS_FAILURE, '2026-09-06 10:00:00');

        $this->assertSame([], $this->idsFrom('filter[status_code]=not-a-status'));
    }

    /**
     * @covers ::queryBuilder
     */
    public function test_an_empty_status_value_leaves_the_page_alone(): void
    {
        $bot = $this->bot();
        $this->runFor($bot, Queue::STATUS_FAILURE, '2026-09-06 10:00:00');

        // The base fixture's crawler is on this page too, which is the point:
        // an empty filter narrows nothing.
        $this->assertContains($bot->id, $this->idsFrom('filter[status_code]='));
        $this->assertContains($this->crawler->id, $this->idsFrom('filter[status_code]='));
    }

    /**
     * @covers ::queryBuilder
     */
    public function test_an_unknown_filter_key_is_still_rejected_outright(): void
    {
        $this->withExceptionHandling();

        $this->actingAs($this->user)
            ->getJson('/api/v1/crawlers?filter[nonsense]=1')
            ->assertStatus(400);
    }

    /**
     * Two runs started in the same second is not hypothetical -- the runner
     * launches a wave of bots at once -- so the tiebreak has to be defined.
     *
     * @covers ::scopeLatestRunStatusIn
     */
    public function test_runs_that_started_in_the_same_second_are_tie_broken_by_id(): void
    {
        $bot = $this->bot();
        $this->runFor($bot, Queue::STATUS_FAILURE, '2026-09-06 10:00:00');
        $this->runFor($bot, Queue::STATUS_SUCCESS, '2026-09-06 10:00:00');

        $this->assertSame([], $this->idsFrom('filter[status_code]=failure'));
        $this->assertSame([$bot->id], $this->idsFrom('filter[status_code]=success'));
    }

    /**
     * The filter is one correlated EXISTS on the list query, not a lookup per
     * row. /crawlers is the page the workspace loads and it already carries six
     * count subselects; this must not turn it into an N+1.
     *
     * @covers ::scopeLatestRunStatusIn
     */
    public function test_the_filter_does_not_fan_out_into_a_query_per_row(): void
    {
        for ($i = 0; $i < 40; $i++) {
            $bot = $this->bot();
            $this->runFor($bot, 0 === $i % 2 ? Queue::STATUS_FAILURE : Queue::STATUS_SUCCESS, '2026-09-06 10:00:00');
        }

        request()->merge(['filter' => ['status_code' => 'failure']]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $page = app(CrawlerRepository::class)->all(50);
        $page->toArray();
        $queries = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertCount(20, $page->items());
        $this->assertLessThanOrEqual(
            10,
            $queries,
            "a filtered 20-row crawler page cost {$queries} queries; a per-row lookup would add one each"
        );
    }

    /**
     * filter[bad]=1 is the same question as filter[status_code]=failure,warning
     * and must give the same answer on this endpoint as it does on /queues and
     * /jobs. It exists so a client never has to spell the predicate out -- and
     * so nobody spells it 'failure' alone, which is how degraded runs stayed
     * invisible in the first place.
     *
     * @covers ::queryBuilder
     */
    public function test_filter_bad_asks_which_bots_need_attention(): void
    {
        $failed = $this->bot();
        $this->runFor($failed, 'failed', '2026-09-06 10:00:00');

        $warned = $this->bot();
        $this->runFor($warned, Queue::STATUS_WARNING, '2026-09-06 10:00:00');

        $ok = $this->bot();
        $this->runFor($ok, Queue::STATUS_SUCCESS, '2026-09-06 10:00:00');

        $running = $this->bot();
        $this->runFor($running, Queue::STATUS_RUNNING, '2026-09-06 10:00:00');

        $this->assertEqualsCanonicalizing(
            [$failed->id, $warned->id],
            $this->idsFrom('filter[bad]=1')
        );

        $this->assertEqualsCanonicalizing(
            $this->idsFrom('filter[status_code]=failure,warning'),
            $this->idsFrom('filter[bad]=1'),
            'filter[bad] and the spelled-out is_bad predicate must select the same bots'
        );
    }

    /**
     * filter[bad]=0 means "show me everything", not "only the good ones".
     * There is no inverse, because inventing one would file every unknown
     * status under healthy.
     *
     * @covers ::queryBuilder
     */
    public function test_filter_bad_off_narrows_nothing(): void
    {
        $failed = $this->bot();
        $this->runFor($failed, Queue::STATUS_FAILURE, '2026-09-06 10:00:00');

        $ids = $this->idsFrom('filter[bad]=0');

        $this->assertContains($failed->id, $ids);
        $this->assertContains($this->crawler->id, $ids);
    }

    /**
     * @return int[]
     */
    private function idsFrom(string $query): array
    {
        $rows = $this->actingAs($this->user)
            ->getJson('/api/v1/crawlers?'.$query)
            ->assertOk()
            ->json('data');

        return array_map(fn (array $row) => $row['id'], $rows);
    }

    private function bot(): Crawler
    {
        return Crawler::factory()->create([
            'proxy_id' => $this->proxy->id,
            'agent_id' => $this->agent->id,
            'experiment_id' => $this->experiment->id,
            'configuration_id' => $this->configuration->id,
            'active' => 1,
            'testing' => 0,
        ]);
    }

    private function runFor(Crawler $crawler, ?string $status, string $startedAt): Queue
    {
        return Queue::factory()->create([
            'crawler_id' => $crawler->id,
            'status_code' => $status,
            'started_at' => $startedAt,
        ]);
    }
}
