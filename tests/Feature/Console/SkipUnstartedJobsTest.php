<?php

namespace Tests\Feature\Console;

use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Job\Model\Job;
use App\Modules\Queue\Model\Queue;
use Tests\TestCase;

/**
 * php artisan jobs:skip-unstarted: Queue::skipUnstartedJobs() for the runs
 * reported before the runner endpoint applied it.
 *
 * @internal
 *
 * @coversDefaultClass \App\Console\Commands\SkipUnstartedJobs
 */
class SkipUnstartedJobsTest extends TestCase
{
    /**
     * @covers ::handle
     */
    public function test_it_marks_only_status_less_jobs_of_finished_bad_runs(): void
    {
        $neverStarted = $this->finishedRun(Queue::STATUS_FAILURE, 1, 'initialisation failed');
        $unstarted = $this->jobOf($neverStarted, null);

        $legacy = $this->finishedRun('failed', 1);
        $legacyJob = $this->jobOf($legacy, null);

        $midway = $this->finishedRun(Queue::STATUS_FAILURE, 1);
        $reported = $this->jobOf($midway, Queue::STATUS_SUCCESS);

        $open = $this->finishedRun(Queue::STATUS_FAILURE, 0);
        $pending = $this->jobOf($open, null);

        $fine = $this->finishedRun(Queue::STATUS_SUCCESS, 1);
        $unknown = $this->jobOf($fine, null);

        $this->artisan('jobs:skip-unstarted')
            ->expectsOutput('Marked 2 job(s) of 2 run(s).')
            ->assertExitCode(0);

        $this->assertSame(Queue::STATUS_SKIPPED, $unstarted->fresh()->status_code);
        $this->assertSame(
            'not run: the run ended failure - initialisation failed',
            $unstarted->fresh()->status_message
        );
        $this->assertSame(Queue::STATUS_SKIPPED, $legacyJob->fresh()->status_code);
        $this->assertSame(Queue::STATUS_SUCCESS, $reported->fresh()->status_code);
        $this->assertNull($pending->fresh()->status_code);
        $this->assertNull($unknown->fresh()->status_code);

        $this->artisan('jobs:skip-unstarted')->expectsOutput('Marked 0 job(s) of 0 run(s).');
    }

    /**
     * @covers ::handle
     */
    public function test_a_dry_run_counts_and_writes_nothing(): void
    {
        $run = $this->finishedRun(Queue::STATUS_FAILURE, 1);
        $job = $this->jobOf($run, null);
        $this->jobOf($run, null);

        $this->artisan('jobs:skip-unstarted', ['--dry-run' => true])
            ->expectsOutput('Would mark 2 job(s) of 1 run(s).')
            ->assertExitCode(0);

        $this->assertNull($job->fresh()->status_code);
    }

    /**
     * @covers ::handle
     */
    public function test_it_can_be_limited_to_an_experiment(): void
    {
        $mine = $this->jobOf($this->finishedRun(Queue::STATUS_FAILURE, 1), null);

        $otherStudy = Experiment::factory()->create(['user_id' => $this->user->id]);
        $otherBot = Crawler::factory()->create([
            'user_id' => $this->user->id,
            'experiment_id' => $otherStudy->id,
            'proxy_id' => $this->proxy->id,
            'agent_id' => $this->agent->id,
            'configuration_id' => $this->configuration->id,
        ]);
        $theirs = $this->jobOf($this->finishedRun(Queue::STATUS_FAILURE, 1, null, $otherBot), null);

        $this->artisan('jobs:skip-unstarted', ['--experiment' => [$otherStudy->id]])
            ->expectsOutput('Marked 1 job(s) of 1 run(s).');

        $this->assertNull($mine->fresh()->status_code);
        $this->assertSame(Queue::STATUS_SKIPPED, $theirs->fresh()->status_code);
    }

    private function finishedRun(string $status, int $processed, ?string $message = null, ?Crawler $bot = null): Queue
    {
        return Queue::factory()->create([
            'crawler_id' => ($bot ?? $this->crawler)->id,
            'status_code' => $status,
            'status_message' => $message,
            'processed' => $processed,
        ]);
    }

    private function jobOf(Queue $queue, ?string $status): Job
    {
        return Job::factory()->create([
            'queue_id' => $queue->id,
            'user_id' => $this->user->id,
            'status_code' => $status,
            'status_message' => null,
        ]);
    }
}
