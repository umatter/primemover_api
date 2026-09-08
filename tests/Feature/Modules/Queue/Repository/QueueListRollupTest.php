<?php

namespace Tests\Feature\Modules\Queue\Repository;

use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use App\Modules\Queue\Repository\QueueRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The job-outcome rollup on the eight queues-* lists.
 *
 * These lists used to eager-load `jobs` in full and count the rollup off the
 * loaded relation, on the reasoning that the numbers are free once the jobs are
 * in memory. The reasoning was sound; the premise was the bug. Hydrating every
 * job of every run peaked at 460.5 MB against a 128 MB limit on production
 * (735 runs / 31,574 jobs), so the three top-level lists answered 500 and
 * served no rollup at all.
 *
 * They are now paginated, count in SQL over the page, and load no jobs -- the
 * shape GET /queues always had. Three things therefore have to hold together:
 * the lists must not hydrate the jobs they are listing, they must not fan the
 * counting out into a query per row, and the numbers must be right -- including
 * the legacy spellings ('failed', 'skpped') that a raw status comparison splits
 * away from their canonical twins, and the three-state decision verdicts, where
 * NULL is unknown and is counted in neither direction.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Queue\Repository\QueueRepository
 */
class QueueListRollupTest extends TestCase
{
    /**
     * The finding itself: a list endpoint must not fetch the jobs it is
     * listing.
     *
     * Asserted on the compiled SQL, because this is invisible to a query count
     * -- the eager load is ONE extra statement whether it returns two job rows
     * or 31,574, and it was the row count, not the statement count, that
     * exhausted the memory limit.
     *
     * @covers ::processed
     */
    public function test_the_list_endpoints_do_not_hydrate_the_jobs_they_are_listing(): void
    {
        $run = $this->aRun(['processed' => 1, 'reviewed' => 1]);
        $this->jobFor($run, Queue::STATUS_SUCCESS);

        foreach ($this->listUrls() as $url) {
            foreach ($this->statementsFor($url) as $sql) {
                $this->assertStringNotContainsString(
                    'from `jobs`',
                    $sql,
                    "{$url} still loads job rows; the seven counts are what a list needs, not the jobs"
                );
            }
        }
    }

    /**
     * ...and does not serve them either. The rollup replaced the relation; it
     * did not join it.
     *
     * @covers ::processed
     */
    public function test_the_list_endpoints_do_not_serve_a_nested_jobs_array(): void
    {
        $run = $this->aRun(['processed' => 1, 'reviewed' => 1]);
        $this->jobFor($run, Queue::STATUS_SUCCESS);

        foreach ($this->listUrls() as $url) {
            foreach ($this->actingAs($this->user)->getJson($url)->assertOk()->json('data') as $row) {
                $this->assertArrayNotHasKey('jobs', $row, $url);
                $this->assertArrayHasKey('jobs_count', $row, $url);
            }
        }
    }

    /**
     * And they are pages, so the peak cost is set by per_page rather than by
     * how much history the installation has.
     *
     * @covers ::processed
     * @covers ::experimentProcessed
     */
    public function test_the_list_endpoints_are_paginated(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->jobFor($this->aRun(['processed' => 1, 'reviewed' => 1]), Queue::STATUS_SUCCESS);
        }

        foreach ($this->listUrls() as $url) {
            $body = $this->actingAs($this->user)->getJson($url.'?per_page=2')->assertOk()->json();

            $this->assertArrayHasKey('meta', $body, "{$url} is not paginated");
            $this->assertSame(2, $body['meta']['per_page'], "{$url} ignores per_page");
            $this->assertLessThanOrEqual(2, count($body['data']), "{$url} served more than one page");
        }
    }

    /**
     * ...and does not pay for that by asking one query per row instead.
     *
     * The failure this guards is the obvious wrong fix: dropping the eager load
     * and letting an accessor or a lazy loadCount() fire per queue. Pagination
     * caps how bad that can get, which is exactly why it would survive review.
     *
     * @covers ::processed
     */
    public function test_the_rollup_costs_no_query_per_row(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $run = $this->aRun(['processed' => 1]);
            $this->jobFor($run, Queue::STATUS_SUCCESS);
            $this->jobFor($run, Queue::STATUS_FAILURE);
        }

        $queries = count($this->statementsFor('/api/v1/queues-processed'));

        $this->assertLessThanOrEqual(
            8,
            $queries,
            "a 31-run queues-processed cost {$queries} queries; a per-row rollup would add 31"
        );
    }

    /**
     * The other half: identical numbers, not approximately identical.
     *
     * Compared row for row against the rollup GET /queues serves, so the two
     * surfaces can never disagree about what jobs_failed_count means. They
     * share an implementation now, which is the point -- this pins that they
     * still do, and that the alias table is inside it.
     *
     * @covers ::processed
     */
    public function test_the_lists_serve_the_same_numbers_as_the_sql_rollup(): void
    {
        $run = $this->aRun(['processed' => 1]);

        // Canonical spellings...
        $this->jobFor($run, Queue::STATUS_SUCCESS);
        $this->jobFor($run, Queue::STATUS_WARNING);
        $this->jobFor($run, Queue::STATUS_FAILURE);
        $this->jobFor($run, Queue::STATUS_SKIPPED);
        // ...and the legacy ones that are real in the live table: 256 job rows
        // spell failure 'failed', and 'skpped' is the runner's own typo.
        $this->jobFor($run, 'failed');
        $this->jobFor($run, 'skpped');
        $this->jobFor($run, 'done');
        // ...plus two jobs that say nothing at all.
        $this->jobFor($run, null);
        $this->jobFor($run, 'running');

        $this->jobFor($run, Queue::STATUS_SUCCESS, ['decision_degraded' => true, 'decision_treatment_lost' => true]);
        $this->jobFor($run, Queue::STATUS_SUCCESS, ['decision_degraded' => true, 'decision_treatment_lost' => false]);

        $fromSql = Queue::query()->withJobOutcomeCounts()->findOrFail($run->id);
        $fromList = $this->listed($run->id);

        foreach (Queue::JOB_OUTCOME_COUNT_ATTRIBUTES as $rollup) {
            $this->assertSame(
                (int) $fromSql->getAttribute($rollup),
                (int) $fromList->getAttribute($rollup),
                "the list rollup and the SQL rollup disagree about {$rollup}"
            );
        }

        // Spelled out, so a change that broke both halves the same way could
        // not pass the comparison above.
        $this->assertSame(11, $fromList->jobs_count);
        $this->assertSame(4, $fromList->jobs_succeeded_count, "'done' is a success, and so are the two decision jobs");
        $this->assertSame(1, $fromList->jobs_warning_count);
        $this->assertSame(2, $fromList->jobs_failed_count, "'failed' and 'failure' are one outcome");
        $this->assertSame(2, $fromList->jobs_skipped_count, "'skpped' is the runner's typo for skipped");
        $this->assertSame(2, $fromList->jobs_degraded_count);
        $this->assertSame(1, $fromList->jobs_treatment_lost_count);
    }

    /**
     * A status nobody has an alias for is UNKNOWN: it counts towards the
     * denominator and towards no outcome bucket. Never success.
     *
     * @covers ::processed
     */
    public function test_an_unrecognised_status_is_counted_in_no_outcome_bucket(): void
    {
        $run = $this->aRun(['processed' => 1]);

        $this->jobFor($run, 'cancelled');
        $this->jobFor($run, null);

        $listed = $this->listed($run->id);

        $this->assertSame(2, $listed->jobs_count);
        $this->assertSame(0, $listed->jobs_succeeded_count);
        $this->assertSame(0, $listed->jobs_warning_count);
        $this->assertSame(0, $listed->jobs_failed_count);
        $this->assertSame(0, $listed->jobs_skipped_count);
    }

    /**
     * A verdict is three-state. Only TRUE is a casualty; NULL is unknown and
     * false is a measured "no", and neither is counted.
     *
     * @covers ::processed
     */
    public function test_a_null_verdict_is_counted_as_neither(): void
    {
        $run = $this->aRun(['processed' => 1]);

        $this->jobFor($run, Queue::STATUS_SUCCESS);
        $this->jobFor($run, Queue::STATUS_SUCCESS, ['decision_treatment_lost' => false, 'decision_degraded' => false]);

        $listed = $this->listed($run->id);

        $this->assertSame(0, $listed->jobs_degraded_count);
        $this->assertSame(0, $listed->jobs_treatment_lost_count);
        $this->assertSame(2, $listed->jobs_succeeded_count);
    }

    /**
     * The pre-migration guard survives the move into PHP: when jobs.decision_*
     * does not exist the two verdict counts are OMITTED, not set to 0. A 0 is
     * the positive claim "nothing here lost its treatment", which an unmigrated
     * database cannot make.
     *
     * @covers ::processed
     */
    public function test_the_verdict_counts_are_omitted_not_zero_without_the_columns(): void
    {
        $run = $this->aRun(['processed' => 1]);
        $this->jobFor($run, Queue::STATUS_SUCCESS);

        $migration = require database_path('migrations/2026_09_07_000001_add_decision_columns_to_jobs_table.php');
        $migration->down();
        $this->assertFalse(Schema::hasColumn('jobs', 'decision_treatment_lost'));

        $attributes = $this->listed($run->id)->getAttributes();

        $this->assertArrayNotHasKey('jobs_treatment_lost_count', $attributes);
        $this->assertArrayNotHasKey('jobs_degraded_count', $attributes);
        $this->assertArrayHasKey('jobs_failed_count', $attributes);
    }

    /**
     * The rollup rides on the model like an attribute but is not a column, so
     * withCount() delivers it in $original where it is never dirty. Saving a
     * queue that came off one of these lists must therefore not try to write a
     * jobs_count column.
     *
     * @covers ::processed
     */
    public function test_the_rollup_does_not_make_the_queue_dirty(): void
    {
        $run = $this->aRun(['processed' => 1]);
        $this->jobFor($run, Queue::STATUS_SUCCESS);

        $listed = $this->listed($run->id);

        $this->assertFalse($listed->isDirty(), 'the rollup left the queue dirty: '.implode(', ', array_keys($listed->getDirty())));

        $listed->update(['description' => 'still saveable']);

        $this->assertDatabaseHas('queues', ['id' => $run->id, 'description' => 'still saveable']);
    }

    /**
     * All eight lists still SERVE the seven numbers -- the point of the finding
     * is the cost of computing them, not the fields.
     *
     * @covers ::processed
     * @covers ::experimentProcessed
     */
    public function test_every_list_endpoint_still_serves_the_whole_rollup(): void
    {
        $run = $this->aRun(['processed' => 1, 'reviewed' => 1]);
        $this->jobFor($run, Queue::STATUS_WARNING);

        foreach ($this->listUrls() as $url) {
            $rows = $this->actingAs($this->user)->getJson($url)->assertStatus(200)->json('data');

            $row = collect($rows)->firstWhere('id', $run->id);

            if (null === $row) {
                // reviewed/unreviewed and processed/unprocessed are
                // complementary; a list that excludes this run has nothing to
                // say about it.
                continue;
            }

            foreach (Queue::JOB_OUTCOME_COUNT_ATTRIBUTES as $rollup) {
                $this->assertArrayHasKey($rollup, $row, $url);
            }

            $this->assertSame(1, $row['jobs_warning_count'], $url);
            $this->assertSame(1, $row['jobs_count'], $url);
        }
    }

    /**
     * @return string[]
     */
    private function listUrls(): array
    {
        return [
            '/api/v1/queues-processed',
            '/api/v1/queues-unprocessed',
            '/api/v1/queues-reviewed',
            '/api/v1/queues-unreviewed',
            '/api/v1/experiments/'.$this->experiment->id.'/queues-processed',
            '/api/v1/experiments/'.$this->experiment->id.'/queues-unprocessed',
            '/api/v1/experiments/'.$this->experiment->id.'/queues-reviewed',
            '/api/v1/experiments/'.$this->experiment->id.'/queues-unreviewed',
        ];
    }

    /**
     * @return string[]
     */
    private function statementsFor(string $url): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($this->user)->getJson($url)->assertStatus(200);

        $statements = array_column(DB::getQueryLog(), 'query');

        DB::disableQueryLog();

        return $statements;
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function aRun(array $attributes = []): Queue
    {
        return Queue::factory()->create(array_merge([
            'crawler_id' => $this->crawler->id,
            'user_id' => $this->user->id,
            'status_code' => Queue::STATUS_SUCCESS,
            'started_at' => now()->subHour()->format('Y-m-d H:i:s'),
        ], $attributes));
    }

    /**
     * @param array<string, mixed> $decision
     */
    private function jobFor(Queue $run, ?string $status, array $decision = []): Job
    {
        return Job::factory()->create(array_merge([
            'queue_id' => $run->id,
            'user_id' => $this->user->id,
            'status_code' => $status,
        ], $decision));
    }

    /**
     * The run as one of the paginated lists returns it.
     */
    private function listed(int $id): Queue
    {
        $listed = (new QueueRepository(new Queue()))->processed()->getCollection()->firstWhere('id', $id);

        $this->assertNotNull($listed, 'the run was not in the processed list');

        return $listed;
    }
}
