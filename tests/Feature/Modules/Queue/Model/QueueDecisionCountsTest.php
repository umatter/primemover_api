<?php

namespace Tests\Feature\Modules\Queue\Model;

use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * The per-run rollup of decision VERDICTS, as opposed to job statuses.
 *
 * jobs_degraded_count and jobs_treatment_lost_count are counted off
 * three-state columns: true, false, or NULL meaning UNKNOWN. Only true is
 * counted, so a zero says "nothing in this run is KNOWN to have degraded" and
 * not "nothing degraded" -- every job that predates the runner deploy is NULL.
 * That distinction is the whole reason these are separate from the five status
 * counts beside them.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Queue\Model\Queue
 */
class QueueDecisionCountsTest extends TestCase
{
    /**
     * @covers ::scopeWithJobOutcomeCounts
     */
    public function test_degraded_and_treatment_lost_jobs_are_counted(): void
    {
        $run = $this->aRun();

        $this->jobFor($run, ['decision_degraded' => true, 'decision_treatment_lost' => true]);
        $this->jobFor($run, ['decision_degraded' => true, 'decision_treatment_lost' => false]);
        $this->jobFor($run, ['decision_degraded' => false, 'decision_treatment_lost' => false]);

        $counted = $this->counted($run);

        $this->assertSame(2, $counted->jobs_degraded_count);
        $this->assertSame(1, $counted->jobs_treatment_lost_count);
        $this->assertSame(3, $counted->jobs_count);
    }

    /**
     * The central property. A NULL verdict is UNKNOWN and is counted in neither
     * direction: it is not a casualty, and it is emphatically not a clean bill
     * of health. Every one of the ~31,500 jobs that predate the migration, and
     * every job that never called the decision function at all, is NULL.
     *
     * @covers ::scopeWithJobOutcomeCounts
     */
    public function test_a_null_verdict_is_counted_as_neither(): void
    {
        $run = $this->aRun();

        // Three jobs that finished perfectly well and say nothing about their
        // treatment.
        $this->jobFor($run, [], Queue::STATUS_SUCCESS);
        $this->jobFor($run, [], Queue::STATUS_SUCCESS);
        $this->jobFor($run, [], Queue::STATUS_SUCCESS);

        $counted = $this->counted($run);

        $this->assertSame(0, $counted->jobs_degraded_count);
        $this->assertSame(0, $counted->jobs_treatment_lost_count);
        // ...while every status count says the run was clean. That is exactly
        // the reading a consumer must not take as "no treatment was lost".
        $this->assertSame(3, $counted->jobs_succeeded_count);
        $this->assertSame(3, $counted->jobs_count);
    }

    /**
     * The verdict and the status are independent. The failure this whole
     * overhaul exists for looks like a total success from every status column:
     * the job ran, the video was clicked, nothing failed, and the data belongs
     * to the wrong arm.
     *
     * @covers ::scopeWithJobOutcomeCounts
     */
    public function test_a_lost_treatment_can_sit_inside_a_run_where_nothing_failed(): void
    {
        $run = $this->aRun(Queue::STATUS_SUCCESS);

        $this->jobFor($run, ['decision_treatment_lost' => true, 'decision_degraded' => true], Queue::STATUS_SUCCESS);
        $this->jobFor($run, [], Queue::STATUS_SUCCESS);

        $counted = $this->counted($run);

        $this->assertSame(0, $counted->jobs_failed_count);
        $this->assertSame(0, $counted->jobs_warning_count);
        $this->assertSame(2, $counted->jobs_succeeded_count);
        $this->assertSame(1, $counted->jobs_treatment_lost_count);
    }

    /**
     * The rollup is what the GUI reads off /queues; it must not cost a query
     * per row, and adding two more count subselects must not change that.
     *
     * @covers ::scopeWithJobOutcomeCounts
     */
    public function test_the_two_new_counts_do_not_fan_out_into_a_query_per_row(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $run = $this->aRun();
            $this->jobFor($run, ['decision_degraded' => true, 'decision_treatment_lost' => true]);
        }

        Passport::actingAs($this->user);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $rows = $this->getJson('/api/v1/queues?per_page=50')->assertOk()->json('data');
        $queries = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            12,
            $queries,
            "a 50-row queue page cost {$queries} queries; a per-row accessor would add 100"
        );

        $row = collect($rows)->first(fn (array $row): bool => $row['jobs_treatment_lost_count'] > 0);

        $this->assertNotNull($row, 'no queue on the page carried the new counts');
        $this->assertSame(1, $row['jobs_degraded_count']);
        $this->assertSame(1, $row['jobs_treatment_lost_count']);
    }

    /**
     * The constant and the scope must not drift: JOB_OUTCOME_COUNT_ATTRIBUTES
     * is what callers strip a serialised queue down to, so a rollup the scope
     * adds and the constant does not know about would silently reappear where
     * a real column is expected.
     *
     * @covers ::scopeWithJobOutcomeCounts
     */
    public function test_the_constant_names_exactly_what_the_scope_adds(): void
    {
        $run = $this->aRun();
        $this->jobFor($run, ['decision_degraded' => true]);

        $attributes = $this->counted($run)->getAttributes();

        foreach (Queue::JOB_OUTCOME_COUNT_ATTRIBUTES as $rollup) {
            $this->assertArrayHasKey($rollup, $attributes, "the scope does not add {$rollup}");
        }

        $added = array_filter(
            array_keys($attributes),
            fn (string $key): bool => str_starts_with($key, 'jobs_') || 'jobs_count' === $key
        );

        $this->assertEqualsCanonicalizing(Queue::JOB_OUTCOME_COUNT_ATTRIBUTES, $added);
    }

    private function aRun(string $status = Queue::STATUS_SUCCESS): Queue
    {
        return Queue::factory()->create([
            'crawler_id' => $this->crawler->id,
            'status_code' => $status,
            'started_at' => now()->subHours(2)->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param array<string, mixed> $decision
     */
    private function jobFor(Queue $run, array $decision, string $status = Queue::STATUS_SUCCESS): Job
    {
        return Job::factory()->create(array_merge([
            'queue_id' => $run->id,
            'user_id' => $this->user->id,
            'status_code' => $status,
        ], $decision));
    }

    private function counted(Queue $run): Queue
    {
        return Queue::query()->withJobOutcomeCounts()->findOrFail($run->id);
    }
}
