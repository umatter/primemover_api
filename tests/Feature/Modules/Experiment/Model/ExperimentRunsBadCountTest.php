<?php

namespace Tests\Feature\Modules\Experiment\Model;

use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Facade\ExperimentRepository;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Queue\Model\Queue;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * runs_bad_count -- the one number an experiment card needs.
 *
 * The experiment list is the screen a researcher opens first and the only one
 * that shows every study at once, and until this count existed it showed the
 * contact address and an updated_at. Answering "which of these needs me?" meant
 * opening each study, paging through its bots 15 at a time and summing the
 * per-bot rollups by hand -- so nobody did, and a study whose runs had been
 * failing since the small hours looked exactly like one that had not.
 *
 * Two properties are load-bearing here and both are regression-guarded below:
 * the count is failure OR warning with every legacy spelling (never failure
 * alone, never the canonical string alone), and it is a count SUBSELECT, so a
 * page of experiments costs the same handful of queries however many there are.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Experiment\Model\Experiment
 */
class ExperimentRunsBadCountTest extends TestCase
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
     * is_bad is failure OR warning (docs/integration-contract.md 3.4).
     *
     * warning is the half that gets forgotten, and it is the half the 2026-08
     * overhaul was built for: the run completed, so nothing else marks it, and
     * it finished 8 of its 204 jobs.
     *
     * @covers ::runsBadInWindow
     */
    public function test_it_counts_warning_as_well_as_failure(): void
    {
        $this->runWithStatus(Queue::STATUS_FAILURE);
        $this->runWithStatus(Queue::STATUS_WARNING);
        $this->runWithStatus(Queue::STATUS_SUCCESS);
        $this->runWithStatus(Queue::STATUS_SKIPPED);
        $this->runWithStatus(Queue::STATUS_RUNNING);
        $this->runWithStatus(null);

        $this->assertSame(2, $this->badCount());
    }

    /**
     * 277 queue rows on the production database spell it 'failed'; the runner
     * wrote 'warn' before the vocabulary settled. Matching only the canonical
     * value would report a study's worst night as one failure out of four.
     *
     * @covers ::runsBadInWindow
     */
    public function test_it_counts_the_legacy_spellings(): void
    {
        $this->runWithStatus('failed');
        $this->runWithStatus('error');
        $this->runWithStatus('fatal');
        $this->runWithStatus('warn');
        $this->runWithStatus('skpped');

        $this->assertSame(4, $this->badCount(), "'skpped' is skipped, and skipped is not bad");
    }

    /**
     * @covers ::queuesInWindow
     */
    public function test_it_only_counts_runs_inside_the_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-07 10:00:00'));

        // 20 hours ago: yesterday by the calendar, inside a rolling 24 hours.
        $this->runWithStatus(Queue::STATUS_FAILURE, '2026-09-06 14:00:00');
        // 44 hours ago.
        $this->runWithStatus(Queue::STATUS_FAILURE, '2026-09-05 14:00:00');
        // Composed for tomorrow, never started: not a failure, not anything.
        Queue::factory()->create([
            'crawler_id' => $this->bot->id,
            'status_code' => null,
            'started_at' => null,
        ]);

        $this->assertSame(1, $this->badCount());
    }

    /**
     * @covers ::queuesInWindow
     */
    public function test_the_window_widens_with_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-07 10:00:00'));

        $this->runWithStatus(Queue::STATUS_FAILURE, '2026-09-06 14:00:00');
        $this->runWithStatus(Queue::STATUS_WARNING, '2026-09-01 14:00:00');

        $this->assertSame(1, $this->badCountFromIndex());
        $this->assertSame(2, $this->badCountFromIndex('?hours=168'));
        $this->assertSame(1, $this->badCountFromIndex('?hours=nonsense'), 'nonsense means the default');
    }

    /**
     * @covers ::runsBadInWindow
     */
    public function test_another_studys_runs_are_not_counted(): void
    {
        $this->runWithStatus(Queue::STATUS_FAILURE);

        $other = Experiment::factory()->create(['user_id' => $this->user->id]);
        $otherBot = $this->botFor($other);
        Queue::factory()->create([
            'crawler_id' => $otherBot->id,
            'status_code' => Queue::STATUS_FAILURE,
            'started_at' => Carbon::now()->subHours(2)->format('Y-m-d H:i:s'),
        ]);

        $this->assertSame(1, $this->badCount());
    }

    /**
     * @covers ::scopeWithRunsBadCount
     */
    public function test_the_experiment_list_carries_the_count(): void
    {
        $this->runWithStatus(Queue::STATUS_WARNING);
        $this->runWithStatus('failed');

        $this->assertSame(2, $this->badCountFromIndex());
    }

    /**
     * Route-model binding has resolved the row before the controller runs, so
     * the show endpoint has to load the count rather than inherit it.
     *
     * @covers ::loadRunsBadCount
     */
    public function test_the_show_endpoint_carries_the_count(): void
    {
        $this->runWithStatus(Queue::STATUS_FAILURE);

        Passport::actingAs($this->user);

        $body = $this->getJson('/api/v1/experiments/'.$this->study->id)
            ->assertOk()
            ->json('data');

        $this->assertSame(1, $body['runs_bad_count']);
    }

    /**
     * The reason this is a subselect and not an accessor.
     *
     * An accessor would count one query per experiment on the one screen that
     * lists every study; a 30-row page would cost 30 extra queries and grow
     * with the account. As a subselect the number below does not move when the
     * page gets longer, which is the property worth protecting.
     *
     * @covers ::scopeWithRunsBadCount
     */
    public function test_the_count_does_not_fan_out_into_a_query_per_experiment(): void
    {
        $this->studiesWithBadRuns(30);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $page = ExperimentRepository::getData()->paginate(30);
        $serialised = $page->toArray();
        $queries = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertCount(30, $page->items());
        $this->assertLessThanOrEqual(
            4,
            $queries,
            "a 30-row experiment page cost {$queries} queries; a per-row accessor would add 30"
        );

        foreach ($serialised['data'] as $row) {
            $this->assertArrayHasKey('runs_bad_count', $row);
        }
    }

    private function badCount(): int
    {
        return (int) Experiment::query()
            ->withRunsBadCount()
            ->findOrFail($this->study->id)
            ->runs_bad_count;
    }

    private function badCountFromIndex(string $query = ''): int
    {
        Passport::actingAs($this->user);

        $rows = $this->getJson('/api/v1/experiments'.$query)
            ->assertOk()
            ->json('data');

        foreach ($rows as $row) {
            if ($this->study->id === $row['id']) {
                return $row['runs_bad_count'];
            }
        }

        $this->fail('the experiment under test was not on the /experiments page');
    }

    private function studiesWithBadRuns(int $count): void
    {
        $startedAt = Carbon::now()->subHours(2)->format('Y-m-d H:i:s');

        for ($i = 0; $i < $count; $i++) {
            $study = Experiment::factory()->create(['user_id' => $this->user->id]);
            $bot = $this->botFor($study);

            Queue::factory()->create([
                'crawler_id' => $bot->id,
                'status_code' => Queue::STATUS_WARNING,
                'started_at' => $startedAt,
            ]);
        }
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

    private function runWithStatus(?string $status, ?string $startedAt = null): Queue
    {
        return Queue::factory()->create([
            'crawler_id' => $this->bot->id,
            'status_code' => $status,
            'started_at' => $startedAt ?? Carbon::now()->subHours(2)->format('Y-m-d H:i:s'),
        ]);
    }
}
