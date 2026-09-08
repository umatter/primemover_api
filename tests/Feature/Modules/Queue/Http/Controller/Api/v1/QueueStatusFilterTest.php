<?php

namespace Tests\Feature\Modules\Queue\Http\Controller\Api\v1;

use App\Modules\Crawler\Model\Crawler;
use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * "Show me the runs that need attention" over HTTP.
 *
 * Until this existed the answer was an error, not a list: status_code was an
 * allowed SORT and not an allowed FILTER, and because there is no
 * config/query-builder.php in this repo Spatie's
 * disable_invalid_filter_query_exception stays false -- so
 * ?filter[status_code]=failure was answered with HTTP 400 rather than being
 * quietly ignored. A researcher's only option was to page through everything.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Queue\Http\Controller\Api\v1\QueueController
 */
class QueueStatusFilterTest extends TestCase
{
    private Crawler $bot;

    protected function setUp(): void
    {
        parent::setUp();

        // A crawler of this test's own, so the two runs the base fixture
        // creates (with a randomised status_code) cannot join the page.
        $this->bot = Crawler::factory()->create([
            'proxy_id' => $this->proxy->id,
            'agent_id' => $this->agent->id,
            'experiment_id' => $this->experiment->id,
            'configuration_id' => $this->configuration->id,
            'active' => 1,
            'testing' => 0,
        ]);
    }

    /**
     * The filter has to expand through the alias table, because the database
     * really holds both spellings: 277 queue rows said 'failed' on 2026-08-27
     * while every failure the current runner writes says 'failure'. Matching
     * the literal would under-report failures by more than half.
     *
     * @covers ::index
     */
    public function test_filtering_by_failure_returns_the_rows_stored_as_failed(): void
    {
        $legacy = $this->queueWith('failed');
        $current = $this->queueWith(Queue::STATUS_FAILURE);
        $this->queueWith(Queue::STATUS_SUCCESS);

        $this->assertSame(
            [$current->id, $legacy->id],
            $this->idsFrom('filter[status_code]=failure')
        );
    }

    /**
     * 'error' and 'fatal' are in the alias table for the same reason; a reader
     * that matches one spelling counts a fraction of the fleet.
     *
     * @covers ::index
     */
    public function test_every_spelling_of_one_outcome_is_one_filter_value(): void
    {
        $this->queueWith('error');
        $this->queueWith('fatal');
        $this->queueWith('failed');
        $this->queueWith(Queue::STATUS_FAILURE);
        $this->queueWith(Queue::STATUS_SUCCESS);

        $this->assertCount(4, $this->idsFrom('filter[status_code]=failure'));
        // ...and asking by a legacy spelling asks the same question.
        $this->assertCount(4, $this->idsFrom('filter[status_code]=failed'));
    }

    /**
     * @covers ::index
     */
    public function test_a_comma_list_selects_several_statuses(): void
    {
        $failed = $this->queueWith(Queue::STATUS_FAILURE);
        $warned = $this->queueWith('warn');
        $this->queueWith(Queue::STATUS_SUCCESS);
        $this->queueWith(Queue::STATUS_SKIPPED);

        $this->assertSame(
            [$warned->id, $failed->id],
            $this->idsFrom('filter[status_code]=failure,warning')
        );
    }

    /**
     * filter[bad]=1 is the contract's is_bad -- failure OR warning, never
     * failure alone -- computed server-side so no client has to carry the alias
     * table, and so nobody writes the query as failure alone. That mistake is
     * what kept degraded runs invisible: a run that completed having lost half
     * its jobs is the one nobody would otherwise look at.
     *
     * @covers ::index
     */
    public function test_bad_returns_exactly_failure_and_warning_and_nothing_else(): void
    {
        $expected = [
            $this->queueWith(Queue::STATUS_FAILURE)->id,
            $this->queueWith('failed')->id,
            $this->queueWith(Queue::STATUS_WARNING)->id,
            $this->queueWith('warn')->id,
        ];

        $this->queueWith(Queue::STATUS_SUCCESS);
        $this->queueWith(Queue::STATUS_SKIPPED);
        $this->queueWith('skpped');
        $this->queueWith(Queue::STATUS_RUNNING);
        $this->queueWith(null);
        $this->queueWith('quarantined');

        $this->assertEqualsCanonicalizing($expected, $this->idsFrom('filter[bad]=1'));
        $this->assertEqualsCanonicalizing(
            $expected,
            $this->idsFrom('filter[status_code]=failure,warning'),
            'filter[bad]=1 must be exactly filter[status_code]=failure,warning'
        );
    }

    /**
     * filter[bad]=0 means "show me everything", not "show me the good ones".
     * There is no inverse predicate: inverting it would quietly file every
     * unknown row under "fine", which is the one thing the contract forbids.
     *
     * @covers ::index
     */
    public function test_bad_switched_off_is_a_no_op_not_an_inversion(): void
    {
        $this->queueWith(Queue::STATUS_FAILURE);
        $this->queueWith(Queue::STATUS_SUCCESS);
        $this->queueWith('quarantined');

        $this->assertCount(3, $this->idsFrom('filter[bad]=0'));
        $this->assertCount(3, $this->idsFrom('filter[bad]='));
    }

    /**
     * A value nobody recognises matches no row -- an empty page, which is a
     * true answer. Never every row, and never a 400.
     *
     * @covers ::index
     */
    public function test_an_unrecognised_status_value_returns_an_empty_page(): void
    {
        $this->queueWith(Queue::STATUS_FAILURE);
        $this->queueWith(Queue::STATUS_SUCCESS);

        $this->assertSame([], $this->idsFrom('filter[status_code]=not-a-status'));
    }

    /**
     * An empty value is a client that sent no selection. Emptying the page for
     * it would make the obvious "filter[status_code]=" + "show all" wiring in a
     * GUI show nothing at all.
     *
     * @covers ::index
     */
    public function test_an_empty_status_value_leaves_the_page_alone(): void
    {
        $this->queueWith(Queue::STATUS_FAILURE);
        $this->queueWith(Queue::STATUS_SUCCESS);

        $this->assertCount(2, $this->idsFrom('filter[status_code]='));
    }

    /**
     * The trap this repo carries: with no config/query-builder.php, Spatie's
     * disable_invalid_filter_query_exception is false, so an unknown filter KEY
     * is a 400 and not a shrug. That is why the filters had to be added rather
     * than assumed to be harmless -- and it stays true for the next one.
     *
     * @covers ::index
     */
    public function test_an_unknown_filter_key_is_still_rejected_outright(): void
    {
        $this->withExceptionHandling();

        $this->actingAs($this->user)
            ->getJson('/api/v1/queues?filter[nonsense]=1')
            ->assertStatus(400);
    }

    /**
     * Ordering the raw string is the bug: 'error', 'failed', 'failure' and
     * 'fatal' are one outcome spelled four ways, and alphabetically they land
     * either side of 'skipped' and 'success'. Severity puts them together.
     *
     * @covers ::index
     */
    public function test_severity_sort_orders_by_meaning_not_by_spelling(): void
    {
        // Deliberately created in an order that is neither the answer nor its
        // reverse.
        $this->queueWith(Queue::STATUS_WARNING);
        $this->queueWith('failed');
        $this->queueWith(Queue::STATUS_SUCCESS);
        $this->queueWith('error');
        $this->queueWith(Queue::STATUS_SKIPPED);
        $this->queueWith(Queue::STATUS_FAILURE);
        $this->queueWith(Queue::STATUS_RUNNING);

        $statuses = $this->statusesFrom('sortBy=severity');

        $this->assertSame(
            ['success', 'skipped', 'running', 'warning', 'failure', 'error', 'failed'],
            $statuses
        );

        // The property that matters, stated directly: every spelling of failure
        // is contiguous.
        $this->assertSame([4, 5, 6], array_keys(array_filter(
            $statuses,
            fn (?string $status) => 5 === Queue::severityRank($status)
        )));
    }

    /**
     * An unrecognised or missing status_code ranks between running and warning
     * -- worse than anything still in flight, not yet evidence of damage. Rank
     * it as success and every unreported row hides at the clean end of the
     * list, which is exactly where nobody looks.
     *
     * @covers ::index
     */
    public function test_unknown_sorts_above_running_and_below_warning(): void
    {
        $this->queueWith(Queue::STATUS_SUCCESS);
        $this->queueWith(Queue::STATUS_SKIPPED);
        $this->queueWith(Queue::STATUS_RUNNING);
        $this->queueWith(null);
        $this->queueWith('quarantined');
        $this->queueWith(Queue::STATUS_WARNING);
        $this->queueWith(Queue::STATUS_FAILURE);

        $this->assertSame(
            ['success', 'skipped', 'running', 'quarantined', null, 'warning', 'failure'],
            $this->statusesFrom('sortBy=severity')
        );
    }

    /**
     * @covers ::index
     */
    public function test_severity_sort_descending_puts_the_worst_first(): void
    {
        $this->queueWith(Queue::STATUS_SUCCESS);
        $this->queueWith('failed');
        $this->queueWith(Queue::STATUS_WARNING);
        $this->queueWith(null);

        $this->assertSame(
            ['failed', 'warning', null, 'success'],
            $this->statusesFrom('sortBy=-severity')
        );
    }

    /**
     * Severity has to REPLACE the default ordering, not be appended to it.
     *
     * Spatie has already applied defaultSort('-id') by the time the repository
     * sees the builder, so "order by id desc, severity" would sort by id and,
     * to no visible effect, by severity -- a sort that looks implemented,
     * answers 200, and does nothing.
     *
     * @covers ::index
     */
    public function test_severity_sort_replaces_the_default_id_ordering(): void
    {
        $oldestFailure = $this->queueWith(Queue::STATUS_FAILURE);
        $newestSuccess = $this->queueWith(Queue::STATUS_SUCCESS);

        $ids = $this->idsFrom('sortBy=-severity');

        $this->assertSame([$oldestFailure->id, $newestSuccess->id], $ids);
        // Without reorder() the default -id sort would have answered this.
        $this->assertNotSame([$newestSuccess->id, $oldestFailure->id], $ids);
    }

    /**
     * ?sort=severity is the Spatie spelling; ?sortBy=severity is the one this
     * endpoint has always used for its own parameter. Both have to work, and
     * they have to agree.
     *
     * @covers ::index
     */
    public function test_the_spatie_sort_parameter_offers_the_same_ordering(): void
    {
        $this->queueWith(Queue::STATUS_WARNING);
        $this->queueWith(Queue::STATUS_SUCCESS);
        $this->queueWith('failed');

        $this->assertSame(
            ['success', 'warning', 'failed'],
            $this->statusesFrom('sort=severity,-id')
        );
    }

    /**
     * A filter and a sort compose; asking for the bad ones worst-first is one
     * request, which is the whole point of the pair.
     *
     * @covers ::index
     */
    public function test_the_filter_and_the_sort_compose(): void
    {
        $this->queueWith(Queue::STATUS_SUCCESS);
        $this->queueWith(Queue::STATUS_WARNING);
        $this->queueWith('failed');

        $this->assertSame(
            ['failed', 'warning'],
            $this->statusesFrom('filter[bad]=1&sortBy=-severity')
        );
    }

    /**
     * The rollup rode on GET /queues alone, so a run-detail page had the jobs
     * and no counts while the list had the counts and no jobs -- the two halves
     * of one question, on two endpoints.
     *
     * @covers ::show
     */
    public function test_the_job_rollup_now_rides_on_the_run_detail_too(): void
    {
        $run = $this->queueWith(Queue::STATUS_WARNING);
        $this->jobsFor($run, [
            Queue::STATUS_SUCCESS,
            Queue::STATUS_SUCCESS,
            Queue::STATUS_WARNING,
            'failed',
            'skpped',
            Queue::STATUS_RUNNING,
        ]);
        $run->jobs()->where('status_code', Queue::STATUS_WARNING)->first()
            ->forceFill(['decision_degraded' => true, 'decision_treatment_lost' => true])->save();

        $body = $this->actingAs($this->user)
            ->getJson('/api/v1/queues/'.$run->id)
            ->assertOk()
            ->json('data');

        $this->assertSame(6, $body['jobs_count']);
        $this->assertSame(2, $body['jobs_succeeded_count']);
        $this->assertSame(1, $body['jobs_warning_count']);
        // Legacy spellings count on the detail page too.
        $this->assertSame(1, $body['jobs_failed_count']);
        $this->assertSame(1, $body['jobs_skipped_count']);
        $this->assertSame(1, $body['jobs_degraded_count']);
        $this->assertSame(1, $body['jobs_treatment_lost_count']);

        // ...and it still carries the jobs, which is why it was worth doing
        // here rather than asking the list endpoint a second time.
        $this->assertCount(6, $body['jobs']);
    }

    /**
     * Every attribute the constant names has to be on the detail response, so
     * the next rollup added cannot be silently missing from one endpoint.
     *
     * @covers ::show
     */
    public function test_the_detail_response_carries_the_whole_rollup_family(): void
    {
        $run = $this->queueWith(Queue::STATUS_SUCCESS);

        $body = $this->actingAs($this->user)
            ->getJson('/api/v1/queues/'.$run->id)
            ->assertOk()
            ->json('data');

        foreach (Queue::JOB_OUTCOME_COUNT_ATTRIBUTES as $attribute) {
            $this->assertArrayHasKey($attribute, $body, $attribute.' is missing from GET /queues/{id}');
        }
    }

    /**
     * The rollup on the detail page is one extra query for the whole family,
     * not one per count -- loadCount(), not seven accessors.
     *
     * @covers ::show
     */
    public function test_the_detail_rollup_costs_one_query_for_all_seven(): void
    {
        $run = $this->queueWith(Queue::STATUS_SUCCESS);
        $this->jobsFor($run, [Queue::STATUS_SUCCESS, Queue::STATUS_WARNING]);

        $fresh = Queue::query()->findOrFail($run->id);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $fresh->loadJobOutcomeCounts();
        $queries = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertSame(1, $queries, 'the rollup must be one aggregate, not one query per count');
        $this->assertSame(2, $fresh->jobs_count);
        $this->assertSame(1, $fresh->jobs_warning_count);
    }

    /**
     * The queues-* families serve the GUI's run lists and had no rollup at all,
     * so showing an outcome beside a run meant a second request per run.
     *
     * @covers ::processed
     * @covers ::unprocessed
     * @covers ::reviewed
     * @covers ::unreviewed
     */
    public function test_the_processed_and_reviewed_lists_carry_the_rollup(): void
    {
        $run = $this->queueWith(Queue::STATUS_WARNING, ['processed' => 1, 'reviewed' => 1]);
        $this->jobsFor($run, [Queue::STATUS_SUCCESS, Queue::STATUS_WARNING, 'failed']);

        foreach (['queues-processed', 'queues-reviewed'] as $endpoint) {
            $row = $this->rowFor($this->actingAs($this->user)->getJson('/api/v1/'.$endpoint)->assertOk()->json('data'), $run->id);

            $this->assertSame(3, $row['jobs_count'], $endpoint.' has no rollup');
            $this->assertSame(1, $row['jobs_warning_count'], $endpoint.' has no rollup');
            $this->assertSame(1, $row['jobs_failed_count'], $endpoint.' has no rollup');
        }

        $pending = $this->queueWith(Queue::STATUS_RUNNING, ['processed' => 0, 'reviewed' => 0]);
        $this->jobsFor($pending, [Queue::STATUS_SUCCESS]);

        $row = $this->rowFor(
            $this->actingAs($this->user)->getJson('/api/v1/queues-unprocessed')->assertOk()->json('data'),
            $pending->id
        );
        $this->assertSame(1, $row['jobs_count']);
    }

    /**
     * ...and so do the four experiment-scoped twins, which are what a workspace
     * page actually calls.
     *
     * @covers ::experimentProcessed
     * @covers ::experimentReviewed
     * @covers ::experimentUnprocessed
     * @covers ::experimentUnreviewed
     */
    public function test_the_experiment_scoped_lists_carry_the_rollup(): void
    {
        $run = $this->queueWith(Queue::STATUS_SUCCESS, ['processed' => 1, 'reviewed' => 1]);
        $this->jobsFor($run, [Queue::STATUS_SUCCESS, 'skpped']);

        foreach (['queues-processed', 'queues-reviewed'] as $endpoint) {
            $row = $this->rowFor(
                $this->actingAs($this->user)
                    ->getJson('/api/v1/experiments/'.$this->experiment->id.'/'.$endpoint)
                    ->assertOk()
                    ->json('data'),
                $run->id
            );

            $this->assertSame(2, $row['jobs_count'], $endpoint.' has no rollup');
            $this->assertSame(1, $row['jobs_skipped_count'], $endpoint.' has no rollup');
        }
    }

    /**
     * The rollup on a list is count SUBSELECTS on the one query. As accessors
     * it would be seven queries per row, which is the regression the /queues
     * list was rewritten to remove.
     *
     * @covers ::processed
     */
    public function test_the_list_rollup_does_not_fan_out_into_a_query_per_row(): void
    {
        $last = null;

        for ($i = 0; $i < 30; $i++) {
            $last = $this->queueWith(Queue::STATUS_SUCCESS, ['processed' => 1]);
            $this->jobsFor($last, [Queue::STATUS_SUCCESS, Queue::STATUS_WARNING]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $rows = app(\App\Modules\Queue\Repository\QueueRepository::class)->processed();
        $rows->toArray();
        $queries = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            10,
            $queries,
            "a 30-row queues-processed page cost {$queries} queries; a per-row accessor would add 210"
        );

        // The list is ordered -id, so the run created last is on the first
        // page whatever else the fixture put in the table.
        $this->assertSame(2, $rows->getCollection()->firstWhere('id', $last->id)->jobs_count);
    }

    /**
     * A filtered page still carries the rollup: the filter narrows the rows, it
     * does not replace the select list.
     *
     * @covers ::index
     */
    public function test_a_filtered_page_still_carries_the_job_rollup(): void
    {
        $run = $this->queueWith(Queue::STATUS_FAILURE);
        $this->jobsFor($run, [Queue::STATUS_WARNING, 'failed']);

        $rows = $this->actingAs($this->user)
            ->getJson('/api/v1/queues?crawler='.$this->bot->id.'&filter[bad]=1')
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $rows);
        $this->assertSame(2, $rows[0]['jobs_count']);
        $this->assertSame(1, $rows[0]['jobs_warning_count']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function page(string $query): array
    {
        return $this->actingAs($this->user)
            ->getJson('/api/v1/queues?crawler='.$this->bot->id.'&'.$query)
            ->assertOk()
            ->json('data');
    }

    /**
     * @return int[]
     */
    private function idsFrom(string $query): array
    {
        return array_column($this->page($query), 'id');
    }

    /**
     * @return array<int, null|string>
     */
    private function statusesFrom(string $query): array
    {
        // array_map, not array_column: a row whose status_code is null is
        // exactly the case under test and must not be dropped from the list.
        return array_map(fn (array $row) => $row['status_code'] ?? null, $this->page($query));
    }

    /**
     * @param  array<int, array<string, mixed>> $rows
     * @return array<string, mixed>
     */
    private function rowFor(array $rows, int $id): array
    {
        foreach ($rows as $row) {
            if ($id === $row['id']) {
                return $row;
            }
        }

        $this->fail('run '.$id.' was not on the page');
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

    private function queueWith(?string $status, array $attributes = []): Queue
    {
        return Queue::factory()->create(array_merge([
            'crawler_id' => $this->bot->id,
            'status_code' => $status,
        ], $attributes));
    }
}
