<?php

namespace Tests\Feature\Modules\Job\Http\Controller\Api\v1;

use App\Modules\Job\Http\Request\RunnerJobUpdateRequest;
use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * The write half of the pre-migration guard.
 *
 * Job::hasDecisionColumns() already keeps the four decision_* columns out of
 * every SELECT until the migration has run. This covers the runner's PUT: with
 * the columns absent the verdict is dropped and the job's terminal state is
 * still recorded.
 *
 * The stake is the job, not the verdict. Both models are $guarded = [], so an
 * un-dropped decision_* field is mass-assigned into an UPDATE naming a column
 * the schema does not have; MySQL answers SQLSTATE[42S22] 1054 and the whole
 * PUT 500s, leaving a finished job at 'running' for ever and taking its run's
 * rollup -- and every analysis that joins on status_code -- with it.
 *
 * Reachable whenever schema and code are out of step in this direction: a
 * migration rolled back under a deployed runner, a restore from a pre-migration
 * dump, or a runner shipped ahead of the documented order.
 *
 * Note this suite runs on sqlite, which does NOT reproduce the 1054 -- an
 * unresolvable double-quoted identifier is re-read there as a string literal.
 * So these assert the observable contract (status recorded, verdict absent)
 * rather than the engine-specific error.
 *
 * @coversNothing
 */
class RunnerJobDecisionWriteGuardTest extends TestCase
{
    private const DECISION_FIELDS = RunnerJobUpdateRequest::DECISION_FIELDS;

    public function test_the_job_terminal_state_is_recorded_when_the_columns_are_absent(): void
    {
        $job = $this->runningJob();
        $this->dropDecisionColumns();

        $this->sendUpdate($job)->assertOk();

        $this->assertSame(
            Queue::STATUS_WARNING,
            $job->fresh()->status_code,
            'losing the status PUT strands a finished job at running for ever; the verdict is the expendable half'
        );
    }

    public function test_the_verdict_is_not_written_as_false_when_the_columns_are_absent(): void
    {
        $job = $this->runningJob();
        $this->dropDecisionColumns();

        $this->sendUpdate($job)->assertOk();

        $row = (array) DB::table('jobs')->where('id', $job->id)->first();

        foreach (self::DECISION_FIELDS as $field) {
            $this->assertArrayNotHasKey(
                $field,
                $row,
                "{$field} must stay absent: absent reads as unknown, false would assert the treatment applied"
            );
        }
    }

    public function test_the_verdict_is_still_persisted_when_the_columns_exist(): void
    {
        $job = $this->runningJob();

        $this->sendUpdate($job)->assertOk();

        $fresh = $job->fresh();
        $this->assertSame(Queue::STATUS_WARNING, $fresh->status_code);
        $this->assertTrue((bool) $fresh->decision_treatment_lost, 'the guard must not suppress a verdict that has somewhere to land');
        $this->assertTrue((bool) $fresh->decision_degraded);
        $this->assertSame('random', $fresh->decision_selection_mode);
    }

    private function runningJob(): Job
    {
        return Job::factory()->create([
            'queue_id' => $this->queue->id,
            'user_id' => $this->user->id,
            'status_code' => Queue::STATUS_RUNNING,
            'type' => 'lambdasingleselectjob',
        ]);
    }

    private function dropDecisionColumns(): void
    {
        Schema::table('jobs', function ($table): void {
            $table->dropColumn(self::DECISION_FIELDS);
        });
        Job::forgetDecisionColumnSupport();
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'status_code' => Queue::STATUS_WARNING,
            'status_message' => 'minor error encountered - lambda reported a degraded decision',
            'decision_degraded' => true,
            'decision_treatment_lost' => true,
            'decision_selection_mode' => 'random',
            'decision_report' => [
                'filters' => ['political' => 'failed: boom'],
                'llm' => ['available' => false],
                'warnings' => ['w'],
                'applied' => [],
                'requested' => ['political'],
            ],
        ];
    }

    private function sendUpdate(Job $job): \Illuminate\Testing\TestResponse
    {
        Passport::actingAs($this->user);

        return $this->putJson('/api/v1/runner/jobs/'.$job->id, $this->payload());
    }
}
