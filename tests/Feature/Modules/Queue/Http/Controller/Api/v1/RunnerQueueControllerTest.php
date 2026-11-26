<?php

namespace Tests\Feature\Modules\Queue\Http\Controller\Api\v1;

use App\Modules\Job\Model\Job;
use App\Modules\Queue\Http\Request\RunnerQueueUpdateRequest;
use App\Modules\Queue\Model\Queue;
use App\Modules\Queue\Repository\QueueRepository;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Passport;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

/**
 * The endpoint the runner reports every run (joblist) outcome to.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Queue\Http\Controller\Api\v1\RunnerQueueController
 */
class RunnerQueueControllerTest extends TestCase
{
    /**
     * Warning, not error: a bot session failing is not a fault in this API, and
     * at error level the API's error rate just tracks the fleet's failure rate.
     *
     * @covers ::update
     */
    public function test_a_failed_run_is_logged_as_a_warning_not_an_api_error(): void
    {
        Log::spy();
        $queue = $this->runningRun();

        $this->updateQueue($queue, [
            'status_code' => Queue::STATUS_FAILURE,
            'status_message' => 'initialisation failed - profile create returned 429',
            'processing' => false,
            'processed' => true,
        ])->assertOk();

        $fresh = $queue->fresh();
        $this->assertSame(Queue::STATUS_FAILURE, $fresh->status_code);
        $this->assertSame(1, (int) $fresh->processed);
        $this->assertSame(0, (int) $fresh->processing);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => 'runner reported queue failure' === $message
                && $context['queue_id'] === $queue->id
                && $context['crawler_id'] === $queue->crawler_id
                && str_contains($context['status_message'], '429'));
        Log::shouldNotHaveReceived('error');
    }

    /**
     * The runner retries a PUT it never got an answer to. One failed run must
     * not read as two.
     *
     * @covers ::update
     */
    public function test_the_same_failed_run_reported_twice_is_logged_once(): void
    {
        Log::spy();
        $queue = $this->runningRun();
        $payload = [
            'status_code' => Queue::STATUS_FAILURE,
            'status_message' => 'initialisation failed - profile create returned 429',
        ];

        $this->updateQueue($queue, $payload)->assertOk();
        $this->updateQueue($queue->fresh(), $payload)->assertOk();

        Log::shouldHaveReceived('warning')->once();
    }

    /**
     * The log line used to precede the write, so a write that threw still left
     * a record of a failure the database never got.
     *
     * @covers ::update
     */
    public function test_a_write_that_fails_logs_nothing(): void
    {
        Log::spy();
        $queue = $this->runningRun();

        $this->mock(
            QueueRepository::class,
            fn (MockInterface $mock) => $mock->shouldReceive('update')
                ->once()
                ->andThrow(new RuntimeException('the database went away'))
        );

        try {
            $this->updateQueue($queue, [
                'status_code' => Queue::STATUS_FAILURE,
                'status_message' => 'initialisation failed - profile create returned 429',
            ]);
            $this->fail('the repository was mocked to throw');
        } catch (RuntimeException $exception) {
            $this->assertSame('the database went away', $exception->getMessage());
        }

        Log::shouldNotHaveReceived('warning');
        Log::shouldNotHaveReceived('error');
    }

    /**
     * Parity with the orchestrator's alias table is not cosmetic: 'error' is a
     * failure to every other reader, and used to be neither failure nor warning
     * here, so a run reporting it left no log line at all.
     *
     * @covers ::update
     */
    public function test_a_legacy_synonym_for_failure_is_recognised(): void
    {
        Log::spy();
        $queue = $this->runningRun();

        $this->updateQueue($queue, [
            'status_code' => 'error',
            'status_message' => 'something went wrong',
        ])->assertOk();

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => 'runner reported queue failure' === $message
                && 'error' === $context['status_code']);
    }

    /**
     * It runs on every status write, 'running' included; only bad news logs.
     *
     * @covers ::update
     */
    public function test_a_running_or_successful_run_logs_nothing(): void
    {
        Log::spy();
        $queue = $this->runningRun();

        $this->updateQueue($queue, [
            'status_code' => Queue::STATUS_RUNNING,
            'status_message' => 'in progress',
            'processing' => true,
        ])->assertOk();

        $this->updateQueue($queue, [
            'status_code' => Queue::STATUS_SUCCESS,
            'status_message' => 'done',
        ])->assertOk();

        Log::shouldNotHaveReceived('error');
        Log::shouldNotHaveReceived('warning');
    }

    /**
     * @covers ::update
     */
    public function test_an_over_long_diagnostic_is_truncated_not_rejected(): void
    {
        $queue = $this->runningRun();

        $this->updateQueue($queue, [
            'status_code' => Queue::STATUS_FAILURE,
            'status_message' => str_repeat('y', 70000),
        ])->assertOk();

        $stored = (string) $queue->fresh()->status_message;

        $this->assertLessThanOrEqual(RunnerQueueUpdateRequest::STATUS_MESSAGE_MAX, mb_strlen($stored, '8bit'));
        $this->assertStringEndsWith(' [truncated]', $stored);
    }

    /**
     * The runner writes the ISO-8601 string these varchar columns hold; the
     * length rule must not start rejecting it.
     *
     * @covers ::update
     */
    public function test_the_runners_timestamp_format_is_accepted(): void
    {
        $queue = $this->runningRun();

        $this->updateQueue($queue, [
            'status_code' => Queue::STATUS_SUCCESS,
            'status_message' => 'done',
            'started_at' => '2026-08-27T07:54:02.884+0200',
            'finished_at' => '2026-08-27T08:19:24.001+0200',
        ])->assertOk();

        $this->assertNotNull($queue->fresh()->finished_at);
    }

    /**
     * @covers ::update
     */
    public function test_a_non_string_status_message_is_rejected(): void
    {
        $this->withExceptionHandling();
        $queue = $this->runningRun();

        $this->updateQueue($queue, ['status_message' => ['a' => 'b']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status_message');
    }

    /**
     * Experiment 240: four bots failed to start 129 runs in a row. Each run was
     * reported 'failure' on the queue, and its jobs -- 13,290 of them -- kept no
     * status at all, which every reader shows as "not run yet".
     *
     * @covers ::update
     */
    public function test_the_jobs_of_a_run_that_never_started_become_skipped(): void
    {
        $queue = $this->runningRun();
        $first = $this->jobOf($queue, null);
        $second = $this->jobOf($queue, null);

        $this->updateQueue($queue, [
            'status_code' => Queue::STATUS_FAILURE,
            'status_message' => 'initialisation failed - browser/profile startup did not complete for crawler 2029',
            'processing' => false,
            'processed' => true,
        ])->assertOk();

        foreach ([$first, $second] as $job) {
            $fresh = $job->fresh();
            $this->assertSame(Queue::STATUS_SKIPPED, $fresh->status_code);
            $this->assertSame(
                'not run: the run ended failure - initialisation failed - '
                    .'browser/profile startup did not complete for crawler 2029',
                $fresh->status_message
            );
        }
    }

    /**
     * A run that died half way: what the runner said about a job stays.
     *
     * @covers ::update
     */
    public function test_a_job_the_runner_reported_on_is_never_overwritten(): void
    {
        $queue = $this->runningRun();
        $done = $this->jobOf($queue, Queue::STATUS_SUCCESS, 'successfully finished job');
        $failed = $this->jobOf($queue, Queue::STATUS_FAILURE, 'click failed');
        $never = $this->jobOf($queue, null);

        $this->updateQueue($queue, [
            'status_code' => Queue::STATUS_FAILURE,
            'status_message' => 'uncaught error',
            'processing' => false,
            'processed' => true,
        ])->assertOk();

        $this->assertSame(Queue::STATUS_SUCCESS, $done->fresh()->status_code);
        $this->assertSame('successfully finished job', $done->fresh()->status_message);
        $this->assertSame(Queue::STATUS_FAILURE, $failed->fresh()->status_code);
        $this->assertSame(Queue::STATUS_SKIPPED, $never->fresh()->status_code);
    }

    /**
     * The runner reports 'init failed' twice: first with the code 'running'
     * (JoblistState RUNNING_INITFAILED), then 'failure' with processed. Only
     * the second says the run is over. And a run that ends 'success' or
     * 'warning' reports on its own jobs, so a status-less job there is a real
     * unknown and stays one.
     *
     * @covers ::update
     *
     * @dataProvider reportsThatLeaveJobsAlone
     *
     * @param array<string, mixed> $report
     */
    public function test_jobs_keep_no_status_while_the_run_is_not_over_or_ended_well(array $report): void
    {
        $queue = $this->runningRun();
        $job = $this->jobOf($queue, null);

        $this->updateQueue($queue, $report)->assertOk();

        $this->assertNull($job->fresh()->status_code);
        $this->assertNull($job->fresh()->status_message);
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function reportsThatLeaveJobsAlone(): array
    {
        return [
            'init failed, still running' => [[
                'status_code' => Queue::STATUS_RUNNING,
                'status_message' => 'init failed',
            ]],
            'failure not yet processed' => [[
                'status_code' => Queue::STATUS_FAILURE,
                'processed' => false,
            ]],
            'success' => [[
                'status_code' => Queue::STATUS_SUCCESS,
                'processed' => true,
            ]],
            'warning' => [[
                'status_code' => Queue::STATUS_WARNING,
                'processed' => true,
            ]],
        ];
    }

    /**
     * A run skipped because its start time had passed never ran a job either.
     *
     * @covers ::update
     */
    public function test_the_jobs_of_a_skipped_run_become_skipped(): void
    {
        $queue = $this->runningRun();
        $job = $this->jobOf($queue, null);

        $this->updateQueue($queue, [
            'status_code' => 'skpped',
            'status_message' => 'timed out - start time was more than 60 minutes ago',
            'processed' => true,
        ])->assertOk();

        $this->assertSame(Queue::STATUS_SKIPPED, $job->fresh()->status_code);
        $this->assertStringStartsWith('not run: the run ended skipped - timed out', $job->fresh()->status_message);
    }

    private function jobOf(Queue $queue, ?string $status, ?string $message = null): Job
    {
        return Job::factory()->create([
            'queue_id' => $queue->id,
            'user_id' => $this->user->id,
            'status_code' => $status,
            'status_message' => $message,
        ]);
    }

    private function runningRun(): Queue
    {
        return Queue::factory()->create([
            'crawler_id' => $this->crawler->id,
            'status_code' => Queue::STATUS_RUNNING,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function updateQueue(Queue $queue, array $payload): \Illuminate\Testing\TestResponse
    {
        Passport::actingAs($this->user);

        return $this->putJson('/api/v1/runner/queues/'.$queue->id, $payload);
    }
}
