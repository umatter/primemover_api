<?php

namespace App\Modules\Queue\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Queue\Http\Request\RunnerQueueUpdateRequest;
use App\Modules\Queue\Model\Queue;
use App\Modules\Queue\Repository\QueueRepository;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Runner - Queues",
 *     description="Runner endpoints for updating queue status and progress"
 * )
 */
class RunnerQueueController extends Controller
{
    public function __construct(protected QueueRepository $repository) {}

    /**
     * @OA\Put(
     *     path="/runner/queues/{queue}",
     *     summary="Update queue status (Runner)",
     *     description="Update queue status, progress, and execution details from automation runner",
     *     operationId="runnerUpdateQueue",
     *     tags={"Runner - Queues"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="queue",
     *         in="path",
     *         description="Queue ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Queue update data from runner",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="status_code",
     *                 type="string",
     *                 enum={"running", "success", "warning", "failure", "skipped"},
     *                 description="Current run status. 'warning' is terminal and has been written by the runner since 2026-08-28: the run completed but lost jobs along the way (JoblistState DONE_JOBSWARNING), with the counts in status_message -- 'failure' stays reserved for a run that produced nothing usable. Consumers must not read 'warning' as success; 'needs attention' is failure OR warning. Not validated against this list: an unknown code is stored as sent rather than rejected, so a new runner state can never leave a run stuck mid-flight.",
     *                 example="running"
     *             ),
     *             @OA\Property(
     *                 property="status_message",
     *                 type="string",
     *                 nullable=true,
     *                 description="Status message or error details",
     *                 example="Processing jobs in queue"
     *             ),
     *             @OA\Property(
     *                 property="processing",
     *                 type="boolean",
     *                 description="Whether queue is currently processing",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="processed",
     *                 type="boolean",
     *                 description="Whether queue has been processed",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="reviewed",
     *                 type="boolean",
     *                 description="Whether queue has been reviewed",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="started_at",
     *                 type="string",
     *                 format="date-time",
     *                 nullable=true,
     *                 description="Queue start timestamp",
     *                 example="2023-12-01T10:30:00Z"
     *             ),
     *             @OA\Property(
     *                 property="finished_at",
     *                 type="string",
     *                 format="date-time",
     *                 nullable=true,
     *                 description="Queue completion timestamp",
     *                 example="2023-12-01T11:30:00Z"
     *             ),
     *             @OA\Property(
     *                 property="progress_percentage",
     *                 type="integer",
     *                 minimum=0,
     *                 maximum=100,
     *                 description="Queue progress percentage",
     *                 example=75
     *             ),
     *             @OA\Property(
     *                 property="jobs_completed",
     *                 type="integer",
     *                 description="Number of jobs completed",
     *                 example=3
     *             ),
     *             @OA\Property(
     *                 property="jobs_failed",
     *                 type="integer",
     *                 description="Number of jobs failed",
     *                 example=0
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Queue updated successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Data Collection Queue"),
     *                 @OA\Property(property="status_code", type="string", example="running"),
     *                 @OA\Property(property="status_message", type="string", nullable=true),
     *                 @OA\Property(property="processing", type="boolean", example=true),
     *                 @OA\Property(property="processed", type="boolean", example=false),
     *                 @OA\Property(property="reviewed", type="boolean", example=false),
     *                 @OA\Property(property="started_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="finished_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="progress_percentage", type="integer", example=75),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Queue not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Queue not found")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="status_code",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The status code field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function update(RunnerQueueUpdateRequest $request, Queue $queue): JsonResource
    {
        $data = $request->validated();

        // Read before the write: afterwards the row *is* the report, so there
        // is nothing left to compare it against.
        $repeat = $this->isRepeatReport($queue, $data);

        $updated = $this->repository->update($data, $queue);

        // A run that ended before its jobs ran says so on the queue only; the
        // jobs would otherwise keep no status and read as "not run yet" for
        // good. Also on a repeat report: it only touches jobs with no status.
        $updated->skipUnstartedJobs();

        if ( ! $repeat) {
            $this->logBadOutcome($updated);
        }

        return JsonResource::make($updated);
    }

    /**
     * True when this report says exactly what the row already says.
     *
     * The runner retries a failed PUT, so the same failed run arrives more than
     * once and two identical log lines read as two failed runs. A run that
     * moves running -> failure, or fails a second time with a different
     * diagnostic, is not a repeat and is logged.
     *
     * @param array<string, mixed> $data
     */
    private function isRepeatReport(Queue $queue, array $data): bool
    {
        if (Queue::normalizeStatus($queue->status_code) !== Queue::normalizeStatus($data['status_code'] ?? null)) {
            return false;
        }

        if ( ! array_key_exists('status_message', $data)) {
            return true;
        }

        return (string) $queue->status_message === (string) $data['status_message'];
    }

    /**
     * Record a failed run that has actually been persisted.
     *
     * Nothing in the API used to notice: a queue could come back
     * 'failure - initialisation failed' and leave no log line anywhere. Runs on
     * every queue update, so it costs a normalise and two comparisons unless
     * the news is actually bad.
     *
     * Called *after* the repository write, and reads the persisted row rather
     * than the request body, so a write that throws no longer leaves a log line
     * announcing a failure the database never recorded.
     *
     * Both bad outcomes are logged, because both are bad: 'warning' is a real,
     * terminal queue state that the runner has written since 2026-08-28 (the
     * run completed but lost jobs along the way), and 73 rows on the production
     * database already carried it while this comment still claimed the state
     * was job-only. "Needs attention" is failure OR warning, never failure
     * alone -- a run that finished degraded is precisely the one nobody would
     * otherwise look at.
     *
     * Warning, not error: a bot session failing is not a fault in this API, and
     * logging it at error level makes the API's error rate track the fleet's
     * failure rate -- one alert firing twice, once under the wrong name. error
     * stays for faults in this service.
     */
    private function logBadOutcome(Queue $queue): void
    {
        $status = Queue::normalizeStatus($queue->status_code);

        if (Queue::STATUS_FAILURE !== $status && Queue::STATUS_WARNING !== $status) {
            return;
        }

        $context = [
            'queue_id' => $queue->id,
            'crawler_id' => $queue->crawler_id,
            'status_code' => $queue->status_code,
            'status_message' => Str::limit((string) $queue->status_message, 500),
        ];

        Log::warning(
            Queue::STATUS_FAILURE === $status
                ? 'runner reported queue failure'
                : 'runner reported degraded queue',
            $context
        );
    }
}
