<?php

namespace Tests\Feature\Modules\Job\Model;

use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The shape of the four decision_* columns.
 *
 * Nullability is not a detail here, it is the contract: NULL means UNKNOWN, and
 * a NOT NULL DEFAULT false migration would have asserted, at the moment it ran,
 * that all ~31,500 historical jobs had applied their treatments -- a claim
 * nobody measured, written into the table everybody joins on. It is also what
 * makes the staged deploy (API migration -> GUI -> runner jar) safe: every
 * intermediate state produces nulls, and nulls render as "no verdict".
 *
 * @internal
 *
 * @coversNothing
 */
class JobDecisionSchemaTest extends TestCase
{
    public function test_the_four_columns_exist(): void
    {
        foreach (['decision_degraded', 'decision_treatment_lost', 'decision_selection_mode', 'decision_report'] as $column) {
            $this->assertTrue(Schema::hasColumn('jobs', $column), "jobs.{$column} is missing");
        }
    }

    /**
     * A job written with none of them set -- which is every job the runner
     * created before the deploy, and every job that never calls the decision
     * function -- must land, and must read back as unknown rather than clean.
     */
    public function test_a_job_with_no_verdict_is_valid_and_reads_as_unknown(): void
    {
        $job = Job::factory()->create([
            'queue_id' => $this->queue->id,
            'user_id' => $this->user->id,
            'status_code' => Queue::STATUS_SUCCESS,
        ]);

        $fresh = $job->fresh();

        $this->assertNull($fresh->decision_degraded);
        $this->assertNull($fresh->decision_treatment_lost);
        $this->assertNull($fresh->decision_selection_mode);
        $this->assertNull($fresh->decision_report);
    }

    /**
     * The casts are what keep three states distinguishable on the wire: a
     * boolean cast passes NULL through untouched, so the column reaches a JSON
     * client as true, false or null and never as 1/0/absent.
     */
    public function test_false_and_null_stay_distinguishable_through_the_casts(): void
    {
        $measured = Job::factory()->create([
            'queue_id' => $this->queue->id,
            'user_id' => $this->user->id,
            'decision_degraded' => false,
            'decision_treatment_lost' => false,
            'decision_report' => ['filters' => ['political' => 'ok']],
        ]);

        $unknown = Job::factory()->create([
            'queue_id' => $this->queue->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertFalse($measured->fresh()->decision_degraded);
        $this->assertNull($unknown->fresh()->decision_degraded);

        $serialised = $measured->fresh()->toArray();
        $this->assertFalse($serialised['decision_degraded']);
        $this->assertSame(['filters' => ['political' => 'ok']], $serialised['decision_report']);
    }
}
