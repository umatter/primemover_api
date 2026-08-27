<?php

namespace Tests\Feature\Modules\Job\Http\Controller\Api\v1;

use App\Modules\Job\Http\Request\RunnerJobUpdateRequest;
use App\Modules\Job\Model\Job;
use App\Modules\Job\Repository\JobRepository;
use App\Modules\Queue\Model\Queue;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Passport;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

/**
 * The endpoint the runner reports every job outcome to.
 *
 * @internal
 *
 * @coversDefaultClass \App\Modules\Job\Http\Controller\Api\v1\RunnerJobController
 */
class RunnerJobControllerTest extends TestCase
{
    /**
     * A degraded job is a completed job: it must be stored and answered 200,
     * exactly like a success, and must not be mistaken for a failure.
     *
     * The message here is a real degradation -- the LLM was unavailable, so a
     * filter the crawler asked for did not run. 'selection_mode=random' alone
     * would not be: that is the correct answer for a control crawler, and the
     * runner does not report those as warnings.
     *
     * @covers ::update
     */
    public function test_a_warning_is_accepted_stored_and_logged_as_a_warning(): void
    {
        Log::spy();
        $job = $this->runningJob();

        $this->updateJob($job, [
            'status_code' => Queue::STATUS_WARNING,
            'status_message' => 'minor error encountered - lambda selection_mode=random, llm unavailable',
        ])->assertOk();

        $fresh = $job->fresh();
        $this->assertSame(Queue::STATUS_WARNING, $fresh->status_code);
        $this->assertStringContainsString('selection_mode=random', $fresh->status_message);

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => 'runner reported degraded job' === $message
                && $context['job_id'] === $job->id
                && Queue::STATUS_WARNING === $context['status_code']);
        Log::shouldNotHaveReceived('error');
    }

    /**
     * Warning, not error. A bot session failing is not a fault in this API, and
     * at error level every failed job inflates the API's error rate until the
     * "API is unhealthy" alert is just a second, mislabelled copy of "jobs are
     * failing".
     *
     * @covers ::update
     */
    public function test_a_failure_is_logged_as_a_warning_not_an_api_error(): void
    {
        Log::spy();
        $job = $this->runningJob();

        $this->updateJob($job, [
            'status_code' => Queue::STATUS_FAILURE,
            'status_message' => 'error during execution - NoSuchElementException: #search-input',
        ])->assertOk();

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => 'runner reported job failure' === $message
                && $context['job_id'] === $job->id
                && Queue::STATUS_FAILURE === $context['status_code']
                && str_contains($context['status_message'], 'NoSuchElementException'));
        Log::shouldNotHaveReceived('error');
    }

    /**
     * The runner retries a PUT it did not get an answer to, and re-reports the
     * terminal state of a job it picks back up. Two identical log lines read as
     * two failed jobs to anyone counting them.
     *
     * @covers ::update
     */
    public function test_the_same_failure_reported_twice_is_logged_once(): void
    {
        Log::spy();
        $job = $this->runningJob();
        $payload = [
            'status_code' => Queue::STATUS_FAILURE,
            'status_message' => 'error during execution - TimeoutException',
        ];

        $this->updateJob($job, $payload)->assertOk();
        $this->updateJob($job->fresh(), $payload)->assertOk();

        Log::shouldHaveReceived('warning')->once();
    }

    /**
     * A second attempt that fails differently is news, and must still log.
     *
     * @covers ::update
     */
    public function test_a_second_different_failure_is_logged_again(): void
    {
        Log::spy();
        $job = $this->runningJob();

        $this->updateJob($job, [
            'status_code' => Queue::STATUS_FAILURE,
            'status_message' => 'error during execution - TimeoutException',
        ])->assertOk();

        $this->updateJob($job->fresh(), [
            'status_code' => Queue::STATUS_FAILURE,
            'status_message' => 'error during execution - NoSuchElementException',
        ])->assertOk();

        Log::shouldHaveReceived('warning')->twice();
    }

    /**
     * The log line used to be written before the repository call, so a write
     * that threw still left a record claiming the job had failed -- a failure
     * the database never got, which is the one kind nobody can go back and
     * check.
     *
     * @covers ::update
     */
    public function test_a_write_that_fails_logs_nothing(): void
    {
        Log::spy();
        $job = $this->runningJob();

        $this->mock(
            JobRepository::class,
            fn (MockInterface $mock) => $mock->shouldReceive('update')
                ->once()
                ->andThrow(new RuntimeException('the database went away'))
        );

        try {
            $this->updateJob($job, [
                'status_code' => Queue::STATUS_FAILURE,
                'status_message' => 'error during execution - disk full',
            ]);
            $this->fail('the repository was mocked to throw');
        } catch (RuntimeException $exception) {
            $this->assertSame('the database went away', $exception->getMessage());
        }

        Log::shouldNotHaveReceived('warning');
        Log::shouldNotHaveReceived('error');
    }

    /**
     * The context is read back off the persisted row, not off the request, so
     * what is logged is what actually landed.
     *
     * @covers ::update
     */
    public function test_the_logged_context_is_the_persisted_row(): void
    {
        Log::spy();
        $job = $this->runningJob();

        $this->updateJob($job, [
            'status_code' => Queue::STATUS_FAILURE,
            'status_message' => str_repeat('z', 70000),
        ])->assertOk();

        $stored = (string) $job->fresh()->status_message;

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => 'runner reported job failure' === $message
                // Truncated by the request rule, then trimmed again for the log
                // line: the context is a prefix of what the column really holds.
                && str_starts_with($stored, rtrim($context['status_message'], '.')));
    }

    /**
     * It runs on every job; a clean one must stay silent.
     *
     * @covers ::update
     */
    public function test_a_clean_job_logs_nothing(): void
    {
        Log::spy();
        $job = $this->runningJob();

        $this->updateJob($job, [
            'status_code' => Queue::STATUS_SUCCESS,
            'status_message' => 'successfully finished job',
        ])->assertOk();

        Log::shouldNotHaveReceived('error');
        Log::shouldNotHaveReceived('warning');
    }

    /**
     * status_message used to be one of ~7 fixed strings and now carries a real
     * diagnostic. Too long must mean truncated, never rejected: the runner
     * swallows a 422 and the job would sit at "running" for ever.
     *
     * @covers ::update
     */
    public function test_an_over_long_diagnostic_is_truncated_not_rejected(): void
    {
        $job = $this->runningJob();

        $this->updateJob($job, [
            'status_code' => Queue::STATUS_FAILURE,
            'status_message' => str_repeat('x', 70000),
        ])->assertOk();

        $stored = (string) $job->fresh()->status_message;

        $this->assertLessThanOrEqual(RunnerJobUpdateRequest::STATUS_MESSAGE_MAX, mb_strlen($stored, '8bit'));
        $this->assertStringEndsWith(' [truncated]', $stored);
    }

    /**
     * Deliberately no in: whitelist -- a state the API has not heard of yet
     * still has to land, or the job is stuck at "running" for ever.
     *
     * @covers ::update
     */
    public function test_an_unknown_status_code_is_still_accepted(): void
    {
        $job = $this->runningJob();

        $this->updateJob($job, ['status_code' => 'quarantined'])->assertOk();

        $this->assertSame('quarantined', $job->fresh()->status_code);
    }

    /**
     * What the hardening is actually for: the models are $guarded = [], so a
     * non-scalar can no longer be mass-assigned into a string column.
     *
     * @covers ::update
     */
    public function test_a_non_string_status_code_is_rejected(): void
    {
        $this->withExceptionHandling();
        $job = $this->runningJob();

        $this->updateJob($job, ['status_code' => ['not', 'a', 'string']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status_code');
    }

    private function runningJob(): Job
    {
        return Job::factory()->create([
            'queue_id' => $this->queue->id,
            'status_code' => Queue::STATUS_RUNNING,
            'status_message' => 'job started',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function updateJob(Job $job, array $payload): \Illuminate\Testing\TestResponse
    {
        Passport::actingAs($this->user);

        return $this->putJson('/api/v1/runner/jobs/'.$job->id, $payload);
    }
}
