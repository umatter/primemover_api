<?php

namespace App\Modules\Job\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Job\Exception\DecisionPayloadException;
use App\Modules\Job\Http\Request\StoreJobRequest;
use App\Modules\Job\Http\Request\UpdateJobRequest;
use App\Modules\Job\Model\Job;
use App\Modules\Job\Repository\JobRepository;
use App\Modules\Job\Service\JobDecisionService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Jobs",
 *     description="Job management endpoints"
 * )
 */
class JobController extends Controller
{
    public function __construct(protected JobRepository $repository) {}

    /**
     * @OA\Get(
     *     path="/jobs",
     *     summary="List all jobs",
     *     description="Get paginated list of jobs",
     *     operationId="getJobs",
     *     tags={"Jobs"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="queue",
     *         in="query",
     *         description="Filter by queue ID",
     *         required=false,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Parameter(
     *         name="filter[recent]",
     *         in="query",
     *         description="Filter recent jobs (days)",
     *         required=false,
     *
     *         @OA\Schema(type="integer", example=30)
     *     ),
     *
     *     @OA\Parameter(
     *         name="filter[status_code]",
     *         in="query",
     *         description="Comma-separated list of statuses, each expanded through the alias table: failure also returns the 256 rows this database spells 'failed' (plus 'error' and 'fatal'), skipped also returns the runner's 'skpped' typo. An unrecognised value matches nothing and returns an empty page; an empty value is no filter at all. An unknown filter KEY is still HTTP 400.",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="failure,warning")
     *     ),
     *
     *     @OA\Parameter(
     *         name="filter[bad]",
     *         in="query",
     *         description="1 = only the jobs a human should look at, exactly equivalent to filter[status_code]=failure,warning. The contract's is_bad predicate (docs/integration-contract.md 3.4), computed server-side so no client has to carry the alias table -- and so nobody writes it as failure alone, which is how degraded jobs stayed invisible. 0 is a no-op, not an inversion.",
     *         required=false,
     *
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *
     *     @OA\Parameter(
     *         name="sortBy",
     *         in="query",
     *         description="'severity' or '-severity': order by what the status_code MEANS -- success 0, skipped 1, running 2, unknown 3, warning 4, failure 5 -- so the four spellings of failure land together and a NULL or unrecognised code ranks above running rather than beside success. Replaces the default -id ordering.",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="-severity")
     *     ),
     *
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Spatie sort list; accepts the same 'severity' / '-severity' as sortBy, and composes (sort=severity,-id).",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="severity,-id")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of jobs",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *
     *                 @OA\Items(
     *                     type="object",
     *
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Job Name"),
     *                     @OA\Property(property="description", type="string", example="Job description"),
     *                     @OA\Property(property="type", type="string", example="visit_webpage_browserbar"),
     *                     @OA\Property(property="status_code", type="string", nullable=true, enum={"running", "success", "warning", "failure", "skipped"}, description="Legacy rows also hold 'failed' and 'skpped'; an unrecognised value is unknown and must never be read as success.", example="success"),
     *                     @OA\Property(property="queue_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                     @OA\Property(property="decision_degraded", type="boolean", nullable=true, description="The decision function's verdict: a filter this crawler REQUESTED did not apply. NULL MEANS UNKNOWN -- never clean and never false. A job that predates the migration, a job that never called the decision function (most jobs), and a job run by a runner that has not been redeployed are all NULL. Render 'no verdict', not a green tick.", example=false),
     *                     @OA\Property(property="decision_treatment_lost", type="boolean", nullable=true, description="The bot was configured to be treated and produced control-arm data. The alarm: this session's data is scientifically void. Always false for a control crawler; NULL means unknown.", example=false),
     *                     @OA\Property(property="decision_selection_mode", type="string", nullable=true, description="'llm' or 'random'. A NEUTRAL DESCRIPTION of how the video was picked -- 'random' is the correct answer for a control crawler and 82% of measured payloads. NEVER branch on it alone.", example="random"),
     *                     @OA\Property(property="decision_report", type="object", nullable=true, description="{filters, llm, warnings, applied, requested}. filters[x] == 'skipped' means NOT REQUESTED and is never a failure. GET /jobs/{id}/decision serves the full stored payload, including for jobs that predate these columns.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request): ResourceCollection
    {
        $queueId = $request->input('queue');
        $jobs = $this->repository->all($queueId, null, $request->input('sortBy'));

        return ResourceCollection::make($jobs);
    }

    /**
     * @OA\Post(
     *     path="/jobs",
     *     summary="Create new job",
     *     description="Create a new job",
     *     operationId="createJob",
     *     tags={"Jobs"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name", "queue_id"},
     *
     *             @OA\Property(property="name", type="string", example="Job Name"),
     *             @OA\Property(property="description", type="string", example="Job description"),
     *             @OA\Property(property="type", type="string", example="visit_webpage_browserbar"),
     *             @OA\Property(property="queue_id", type="integer", example=1)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Job created successfully",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreJobRequest $request): JsonResource
    {
        $job = $this->repository->save($request->validated());

        return JsonResource::make($job);
    }

    /**
     * @OA\Get(
     *     path="/jobs/{id}",
     *     summary="Get specific job",
     *     description="Get details of a specific job",
     *     operationId="getJob",
     *     tags={"Jobs"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Job ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Job details, including the decision function's verdict",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=34226),
     *                 @OA\Property(property="status_code", type="string", nullable=true, enum={"running", "success", "warning", "failure", "skipped"}, example="success"),
     *                 @OA\Property(property="status_message", type="string", nullable=true),
     *                 @OA\Property(property="decision_degraded", type="boolean", nullable=true, description="The decision function's verdict: a filter this crawler REQUESTED did not apply. NULL MEANS UNKNOWN -- never clean and never false. A job that predates the migration, a job that never called the decision function (most jobs), and a job run by a runner that has not been redeployed are all NULL. Render 'no verdict', not a green tick.", example=false),
     *                 @OA\Property(property="decision_treatment_lost", type="boolean", nullable=true, description="The bot was configured to be treated and produced control-arm data. The alarm: this session's data is scientifically void. Always false for a control crawler; NULL means unknown.", example=false),
     *                 @OA\Property(property="decision_selection_mode", type="string", nullable=true, description="'llm' or 'random'. A NEUTRAL DESCRIPTION of how the video was picked -- 'random' is the correct answer for a control crawler and 82% of measured payloads. NEVER branch on it alone.", example="random"),
     *                 @OA\Property(property="decision_report", type="object", nullable=true, description="{filters, llm, warnings, applied, requested}. filters[x] == 'skipped' means NOT REQUESTED and is never a failure. GET /jobs/{id}/decision serves the full stored payload, including for jobs that predate these columns.")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Job not found")
     * )
     */
    public function show($id): JsonResource
    {
        $job = $this->repository->getById($id);

        return JsonResource::make($job);
    }

    public function edit(Job $job): JsonResource
    {
        $this->authorize('view', $job);

        return JsonResource::make($job);
    }

    /**
     * @OA\Put(
     *     path="/jobs/{id}",
     *     summary="Update job",
     *     description="Update an existing job",
     *     operationId="updateJob",
     *     tags={"Jobs"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Job ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="queue_id", type="integer")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Job updated successfully",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Job not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateJobRequest $request, Job $job): JsonResource
    {
        $this->authorize('update', $job);

        return JsonResource::make($this->repository->update($request->validated(), $job));
    }

    /**
     * @OA\Delete(
     *     path="/jobs/{id}",
     *     summary="Delete job",
     *     description="Delete an existing job",
     *     operationId="deleteJob",
     *     tags={"Jobs"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Job ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Job deleted successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Job not found")
     * )
     *
     * @throws Exception
     */
    public function destroy(Job $job): ResourceCollection
    {
        $this->authorize('delete', $job);

        $this->repository->delete($job);

        return ResourceCollection::make(
            Job::with(['behaviors', 'reports:id,job_id,path,filename'])->get()
        );
    }

    /**
     * @OA\Get(
     *     path="/jobs/{id}/decision",
     *     summary="The decision function's response for a job",
     *     description="Serves the parsed lambda-jobdata payload recorded for this job, straight from the runner's object store. This is what makes the HISTORICAL verdicts readable: the four decision_* columns on the job row only describe jobs run after the runner deploy, while the ~54000 stored objects go back to the start.",
     *     operationId="getJobDecision",
     *     tags={"Jobs"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="id", in="path", description="Job ID", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="The decision function's response, with the verdict already read off it",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="data", type="object", description="The decision function's own response: xpath, present, selected, procedure, timestamps, warnings and, when the function was new enough to emit them, degraded / treatment_lost / selection_mode / filters / llm."),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="job_id", type="integer", example=34226),
     *                 @OA\Property(property="report_id", type="integer", example=91455),
     *                 @OA\Property(property="key", type="string", example="reports/lambda-jobdata-lambdaselectjob-youtube_video_select-34226"),
     *                 @OA\Property(property="entry", type="string", example="20260907-153436_lambda_output_jobdata_34226.json"),
     *                 @OA\Property(property="invocation", type="object", description="The runner's envelope: statusCode/success/functionError. These describe the CALL, which is 200 even when every requested filter failed. Never a verdict."),
     *                 @OA\Property(
     *                     property="verdict",
     *                     type="object",
     *                     description="The contract's rules applied once, here, so no client has to reimplement them. reported=false means the payload predates the degradation report and the answer is UNKNOWN, not clean.",
     *                     @OA\Property(property="reported", type="boolean", example=true),
     *                     @OA\Property(property="degraded", type="boolean", example=false),
     *                     @OA\Property(property="treatment_lost", type="boolean", example=false),
     *                     @OA\Property(property="selection_mode", type="string", example="random"),
     *                     @OA\Property(property="failed_filters", type="object"),
     *                     @OA\Property(property="skipped_filters", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="label", type="string", example="ok")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="No decision payload was ever recorded for this job"),
     *     @OA\Response(response=502, description="A payload was recorded and the object store would not give it up. Missing evidence about the job, never evidence that the job was fine.")
     * )
     */
    public function decision(Job $job, JobDecisionService $decisions): JsonResponse
    {
        $this->authorize('view', $job);

        $report = $decisions->reportFor($job);

        if (null === $report) {
            // Most jobs never call the decision function at all, so this is the
            // ordinary answer for a visit, a wait or a type job. It says
            // nothing was recorded -- not that nothing went wrong.
            App::abort(404, 'No decision payload was recorded for this job');
        }

        try {
            $payload = $decisions->payloadForReport($report);
        } catch (DecisionPayloadException $exception) {
            // Deliberately NOT a 404. A 404 here would read as "this job made no
            // decision", which is the one thing we know is false: the pointer row
            // exists. 502 says the evidence exists and we could not fetch it.
            Log::warning('1757260001: decision payload unreadable', [
                'job_id' => $job->id,
                'report_id' => $report->id,
                'message' => $exception->getMessage(),
            ]);

            App::abort(502, 'The recorded decision payload could not be read: '.$exception->getMessage());
        }

        $verdict = $payload->verdict();

        return response()->json([
            'data' => $payload->payload,
            'meta' => [
                'job_id' => $job->id,
                'report_id' => $report->id,
                'key' => $report->path,
                'entry' => $payload->entry,
                'invocation' => $payload->invocation,
                'verdict' => [
                    'reported' => $verdict->reported,
                    'degraded' => $verdict->degraded,
                    'treatment_lost' => $verdict->treatmentLost,
                    'selection_mode' => $verdict->selectionMode,
                    'failed_filters' => $verdict->failedFilters(),
                    'skipped_filters' => $verdict->skippedFilters(),
                    'label' => $verdict->label(),
                ],
            ],
        ]);
    }
}
