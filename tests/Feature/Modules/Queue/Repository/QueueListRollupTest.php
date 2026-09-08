<?php

namespace Tests\Feature\Modules\Queue\Repository;

use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use App\Modules\Queue\Repository\QueueRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The job-outcome rollup on the eight unpaginated queues-* lists.
 *
 * Those endpoints eager-load `jobs` in full and serialise the whole relation,
 * so the seven rollup numbers are already in PHP memory by the time anything
 * asks for them. Asking the database to recount them costs seven correlated
 * count subselects per row for nothing: on the rehearsal copy of production
 * (735 queues / 31,574 jobs) the subselects took 87 ms while fetching every one
 * of those 31,574 job rows took 2 ms.
 *
 * Two things therefore have to hold together, and neither is worth much alone:
 * the lists must not emit the subselects, and the numbers they serve must be
 * the same numbers the SQL rollup would have produced -- including the legacy
 * spellings ('failed', 'skpped') that a raw status comparison splits away from
 * their canonical twins, and including the three-state decision verdicts, where
 * NULL is unknown and is counted in neither direction.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Queue\Repository\QueueRepository
 */
class QueueListRollupTest extends TestCase
{
    /**
     * The finding itself: the lists must not ask SQL to recount jobs they have
     * just fetched.
     *
     * Asserted on the compiled SQL rather than on a wall-clock number, because
     * a query-count assertion cannot see this -- the subselects ride inside the
     * ONE list query, so adding or removing them does not change how many
     * statements run, only how much work each does.
     *
     * @covers ::processed
     */
    public function test_the_list_endpoints_do_not_recount_jobs_they_have_already_fetched(): void
    {
        $run = $this->aRun(['processed' => 1, 'reviewed' => 1]);
        $this->jobFor($run, Queue::STATUS_SUCCESS);

        foreach ($this->listUrls() as $url) {
            $statements = $this->statementsFor($url);

            foreach (Queue::JOB_OUTCOME_COUNT_ATTRIBUTES as $rollup) {
                foreach ($statements as $sql) {
                    $this->assertStringNotContainsString(
                        $rollup,
                        $sql,
                        "{$url} still asks the database for {$rollup}, over jobs it has already loaded"
                    );
                }
            }
        }
    }

    /**
     * ...and does not pay for that by asking one query per row instead.
     *
     * The failure this guards is the obvious wrong fix: dropping the subselects
     * and letting an accessor or a lazy loadCount() fire per queue. These lists
     * are unpaginated, so on production sizes that would be 735 extra queries
     * where there were none.
     *
     * @covers ::processed
     */
    public function test_the_php_rollup_costs_no_queries_at_all(): void
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
     * Compared row for row against the SQL rollup the paginated GET /queues
     * still uses, so the two surfaces can never disagree about what
     * jobs_failed_count means.
     *
     * @covers ::processed
     */
    public function test_the_php_rollup_matches_the_sql_rollup_row_for_row(): void
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
        $fromPhp = $this->listed($run->id);

        foreach (Queue::JOB_OUTCOME_COUNT_ATTRIBUTES as $rollup) {
            $this->assertSame(
                (int) $fromSql->getAttribute($rollup),
                (int) $fromPhp->getAttribute($rollup),
                "the in-memory rollup and the SQL rollup disagree about {$rollup}"
            );
        }

        // Spelled out, so a change that broke both halves the same way could
        // not pass the comparison above.
        $this->assertSame(11, $fromPhp->jobs_count);
        $this->assertSame(4, $fromPhp->jobs_succeeded_count, "'done' is a success, and so are the two decision jobs");
        $this->assertSame(1, $fromPhp->jobs_warning_count);
        $this->assertSame(2, $fromPhp->jobs_failed_count, "'failed' and 'failure' are one outcome");
        $this->assertSame(2, $fromPhp->jobs_skipped_count, "'skpped' is the runner's typo for skipped");
        $this->assertSame(2, $fromPhp->jobs_degraded_count);
        $this->assertSame(1, $fromPhp->jobs_treatment_lost_count);
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
     * The rollup rides on the model like an attribute but is not a column, and
     * withCount() delivers it in $original where it is never dirty. The PHP
     * twin has to land the same way, or saving a queue that came off one of
     * these lists would try to write a jobs_count column.
     *
     * @covers \App\Modules\Queue\Model\Queue::rollupFromLoadedJobs
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
     * A caller that forgets the eager load gets the right answers from SQL
     * rather than a row of zeros.
     *
     * @covers \App\Modules\Queue\Model\Queue::rollupFromLoadedJobs
     */
    public function test_it_falls_back_to_the_subselects_when_the_jobs_are_not_loaded(): void
    {
        $run = $this->aRun(['processed' => 1]);
        $this->jobFor($run, Queue::STATUS_FAILURE);
        $this->jobFor($run, 'failed');

        $bare = Queue::query()->findOrFail($run->id);
        $this->assertFalse($bare->relationLoaded('jobs'));

        $this->assertSame(2, $bare->rollupFromLoadedJobs()->jobs_failed_count);
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
     * The run as one of the unpaginated lists returns it.
     */
    private function listed(int $id): Queue
    {
        $listed = (new QueueRepository(new Queue()))->processed()->firstWhere('id', $id);

        $this->assertNotNull($listed, 'the run was not in the processed list');

        return $listed;
    }
}
