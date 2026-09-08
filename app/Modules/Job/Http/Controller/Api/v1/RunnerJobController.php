<?php

namespace App\Modules\Job\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Job\Http\Request\RunnerJobUpdateRequest;
use App\Modules\Job\Model\Job;
use App\Modules\Job\Repository\JobRepository;
use App\Modules\Queue\Model\Queue;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Runner - Jobs",
 *     description="Runner endpoints for updating job status and progress"
 * )
 */
class RunnerJobController extends Controller
{
    public function __construct(protected JobRepository $repository) {}

    /**
     * @OA\Put(
     *     path="/api/v1/runner/jobs/{job}",
     *     summary="Update job status (Runner)",
     *     description="Update job status, progress, and results from automation runner",
     *     operationId="runnerUpdateJob",
     *     tags={"Runner - Jobs"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="job",
     *         in="path",
     *         description="Job ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Job update data from runner",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="status_code",
     *                 type="string",
     *                 enum={"running", "success", "warning", "failure", "skipped"},
     *                 description="Current job status. 'warning' is terminal: the job ran to completion but something the experiment cares about degraded -- job data was not written, or the decision function could not apply a filter the crawler had requested. A decision answering selection_mode 'random' is NOT on its own a warning: for a control crawler that requested no filter it is the correct answer. Not validated against this list -- an unknown code is stored as sent, so a new runner state can never leave a job stuck at 'running'.",
     *                 example="success"
     *             ),
     *             @OA\Property(
     *                 property="status_message",
     *                 type="string",
     *                 nullable=true,
     *                 description="Status message or error details",
     *                 example="Job completed successfully"
     *             ),
     *             @OA\Property(
     *                 property="started_at",
     *                 type="string",
     *                 format="date-time",
     *                 nullable=true,
     *                 description="Job start timestamp",
     *                 example="2023-12-01T10:30:00Z"
     *             ),
     *             @OA\Property(
     *                 property="finished_at",
     *                 type="string",
     *                 format="date-time",
     *                 nullable=true,
     *                 description="Job completion timestamp",
     *                 example="2023-12-01T10:35:00Z"
     *             ),
     *             @OA\Property(
     *                 property="progress",
     *                 type="integer",
     *                 minimum=0,
     *                 maximum=100,
     *                 description="Job progress percentage",
     *                 example=100
     *             ),
     *             @OA\Property(
     *                 property="captcha_found",
     *                 type="boolean",
     *                 nullable=true,
     *                 description="Whether captcha was encountered",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="captcha_solved",
     *                 type="boolean",
     *                 nullable=true,
     *                 description="Whether captcha was solved",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="result_data",
     *                 type="object",
     *                 nullable=true,
     *                 description="Job execution results",
     *                 example={"pages_visited": 5, "data_collected": 150}
     *             ),
     *             @OA\Property(
     *                 property="decision_degraded",
     *                 type="boolean",
     *                 nullable=true,
     *                 description="The decision function's 'degraded': a filter the crawler REQUESTED failed to apply, or nothing could be selected. Omit it (NULL) when there is no verdict -- NULL means unknown, never clean and never false.",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="decision_treatment_lost",
     *                 type="boolean",
     *                 nullable=true,
     *                 description="The decision function's 'treatment_lost': degraded AND nothing requested applied, so a bot configured to be treated produced control-arm data. The alarm. Always false for a control crawler.",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="decision_selection_mode",
     *                 type="string",
     *                 maxLength=32,
     *                 nullable=true,
     *                 description="'llm' or 'random'. A NEUTRAL DESCRIPTION of how the video was chosen; 'random' is the correct answer for a control crawler and is not an error. Never branch on it alone. Not validated against a list -- an unknown mode is stored as sent.",
     *                 example="random"
     *             ),
     *             @OA\Property(
     *                 property="decision_report",
     *                 type="object",
     *                 nullable=true,
     *                 description="{filters, llm, warnings, applied, requested}. filters[x] == 'skipped' means NOT REQUESTED and is never a failure. Shrunk member by member rather than rejected if it exceeds 60000 bytes encoded, and marked _truncated when that happens.",
     *                 example={"filters": {"children_entertainment": "ok", "political": "skipped"}, "llm": {"available": true, "error_type": null, "error": null}, "warnings": {}}
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Job updated successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Web Scraping Job"),
     *                 @OA\Property(property="status_code", type="string", example="success"),
     *                 @OA\Property(property="status_message", type="string", nullable=true),
     *                 @OA\Property(property="progress", type="integer", example=100),
     *                 @OA\Property(property="started_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="finished_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="captcha_found", type="boolean", nullable=true),
     *                 @OA\Property(property="captcha_solved", type="boolean", nullable=true),
     *                 @OA\Property(property="decision_degraded", type="boolean", nullable=true),
     *                 @OA\Property(property="decision_treatment_lost", type="boolean", nullable=true),
     *                 @OA\Property(property="decision_selection_mode", type="string", nullable=true, example="random"),
     *                 @OA\Property(property="decision_report", type="object", nullable=true),
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
     *         description="Job not found",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Job not found")
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
    public function update(RunnerJobUpdateRequest $request, Job $job): JsonResource
    {
        $data = $request->validated();

        // Read before the write: afterwards the row *is* the report, so there
        // is nothing left to compare it against.
        $repeat = $this->isRepeatReport($job, $data);

        $updated = $this->repository->update($data, $job);

        if ( ! $repeat) {
            $this->logBadOutcome($updated);
        }

        return JsonResource::make($updated);
    }

    /**
     * True when this report says exactly what the row already says.
     *
     * The runner retries a failed PUT, and it re-reports the terminal state of
     * a job it picks back up, so the same failure arrives more than once. Two
     * identical log lines read as two failed jobs to anyone counting them.
     *
     * Only the outcome is compared -- a genuine second attempt that fails
     * differently, or a job that moves running -> failure, is not a repeat and
     * is logged.
     *
     * @param array<string, mixed> $data
     */
    private function isRepeatReport(Job $job, array $data): bool
    {
        if (Queue::normalizeStatus($job->status_code) !== Queue::normalizeStatus($data['status_code'] ?? null)) {
            return false;
        }

        if ( ! array_key_exists('status_message', $data)) {
            return true;
        }

        return (string) $job->status_message === (string) $data['status_message'];
    }

    /**
     * Record a failure or a degraded run that has actually been persisted.
     *
     * Nothing in the API used to notice these: a job could fail and the only
     * trace was the row itself. Runs on every job update, so it does nothing
     * but a normalise and two comparisons unless the news is actually bad.
     *
     * Called *after* the repository write, and reads the persisted row rather
     * than the request body. Logging the request first meant a write that threw
     * still left a log line claiming the job had failed -- a failure the
     * database never recorded, which is the one kind of failure nobody can go
     * back and check.
     *
     * 'warning' is a job-only terminal state (see Queue::STATUS_WARNING): the
     * job finished, but its data was not written or the decision function could
     * not apply a filter the experiment asked for.
     *
     * Both outcomes log at warning, not error. A job failing is not an API
     * fault: at error level every failed bot session inflates the API's error
     * rate, so the "API is unhealthy" alert becomes a second, mislabelled copy
     * of "jobs are failing" and stops meaning anything on its own. error stays
     * for faults in this service. The two lines carry different messages, so a
     * log query can still tell a failure from a degradation.
     */
    private function logBadOutcome(Job $job): void
    {
        $status = Queue::normalizeStatus($job->status_code);

        if (Queue::STATUS_FAILURE !== $status && Queue::STATUS_WARNING !== $status) {
            return;
        }

        $context = [
            'job_id' => $job->id,
            'queue_id' => $job->queue_id,
            'status_code' => $job->status_code,
            // The message is a free-text diagnostic since 2026-08-27 and the
            // column holds up to 60000 bytes; keep the log line readable.
            'status_message' => Str::limit((string) $job->status_message, 500),
        ];

        Log::warning(
            Queue::STATUS_FAILURE === $status
                ? 'runner reported job failure'
                : 'runner reported degraded job',
            $context
        );
    }
}
