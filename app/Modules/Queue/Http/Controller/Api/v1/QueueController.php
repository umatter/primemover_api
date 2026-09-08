<?php

namespace App\Modules\Queue\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Queue\Http\Request\StoreQueueRequest;
use App\Modules\Queue\Http\Request\UpdateQueueRequest;
use App\Modules\Queue\Model\Queue;
use App\Modules\Queue\Repository\QueueRepository;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * @OA\Tag(
 *     name="Queues",
 *     description="Queue management and status endpoints"
 * )
 */
class QueueController extends Controller
{
    public function __construct(protected QueueRepository $repository) {}

    /**
     * @OA\Get(
     *     path="/queues",
     *     summary="List all queues",
     *     description="Get paginated list of queues",
     *     operationId="getQueues",
     *     tags={"Queues"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="crawler", in="query", description="Filter by crawler ID", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(
     *         name="filter[status_code]",
     *         in="query",
     *         description="Comma-separated list of statuses. Every value is expanded through the alias table, so failure also returns the rows this database spells 'failed', 'error' and 'fatal', and skipped also returns the runner's 'skpped' typo. An unrecognised value matches nothing and returns an empty page; an empty value is no filter at all. An unknown filter KEY is still HTTP 400.",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="failure,warning")
     *     ),
     *
     *     @OA\Parameter(
     *         name="filter[bad]",
     *         in="query",
     *         description="1 = only the runs a human should look at, exactly equivalent to filter[status_code]=failure,warning. This is the contract's is_bad predicate (docs/integration-contract.md 3.4) computed server-side; it is never failure alone, because a run that completed having lost work is the one nobody would otherwise see. 0 is a no-op, not an inversion.",
     *         required=false,
     *
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *
     *     @OA\Parameter(
     *         name="sortBy",
     *         in="query",
     *         description="Sort field. 'severity' (and '-severity') orders by what the status_code MEANS -- success 0, skipped 1, running 2, unknown 3, warning 4, failure 5 -- so the four spellings of failure land together and an unrecognised or NULL code ranks above running rather than beside success. Severity replaces the default ordering; any other value is appended to it.",
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
     *         description="List of queues, each carrying its job rollup",
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
     *                     @OA\Property(property="id", type="integer", example=849),
     *                     @OA\Property(property="crawler_id", type="integer", example=14),
     *                     @OA\Property(
     *                         property="status_code",
     *                         type="string",
     *                         nullable=true,
     *                         enum={"running", "success", "warning", "failure", "skipped"},
     *                         description="The run's own verdict on itself. Legacy rows also hold 'failed' and 'skpped'; an unrecognised value is unknown and must never be read as success.",
     *                         example="warning"
     *                     ),
     *                     @OA\Property(property="status_message", type="string", nullable=true, example="8/204 jobs succeeded (0 warning, 49 failed, 147 skipped)"),
     *                     @OA\Property(property="jobs_count", type="integer", description="Every job in the run, whatever its state -- the progress denominator. Jobs still running, or with no status_code yet, are counted only here.", example=204),
     *                     @OA\Property(property="jobs_succeeded_count", type="integer", description="Jobs whose status_code is success (or a legacy synonym).", example=8),
     *                     @OA\Property(property="jobs_warning_count", type="integer", description="Jobs that finished but degraded: job data was not written, or the decision function could not apply a filter the experiment requested. Nothing else marks them -- they finished -- which is why this count is the one worth showing.", example=0),
     *                     @OA\Property(property="jobs_failed_count", type="integer", description="Jobs whose status_code is failure (or the legacy 'failed').", example=49),
     *                     @OA\Property(property="jobs_skipped_count", type="integer", description="Jobs never attempted, normally because an earlier job in the run failed (also the legacy 'skpped').", example=147),
     *                     @OA\Property(property="jobs_degraded_count", type="integer", description="Jobs whose decision_degraded is true: a filter the crawler REQUESTED did not apply. A count of verdicts, not of statuses -- a NULL verdict is unknown and is counted in neither direction, so 0 is not a clean bill of health unless a verdict exists.", example=0),
     *                     @OA\Property(property="jobs_treatment_lost_count", type="integer", description="Jobs whose decision_treatment_lost is true: the bot was configured to be treated and produced control-arm data. The alarm. Also counted off a three-state column, so 0 means 'nothing here is KNOWN to have lost its treatment'.", example=0),
     *                     @OA\Property(property="started_at", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="finished_at", type="string", format="date-time", nullable=true)
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
        $crawlerId = $request->input('crawler');
        $sortBy = $request->input('sortBy');
        $queues = $this->repository->all($crawlerId, $sortBy);

        return ResourceCollection::make($queues);
    }

    /**
     * @OA\Post(
     *     path="/queues",
     *     summary="Create new queue",
     *     description="Create a new queue",
     *     operationId="createQueue",
     *     tags={"Queues"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(required={"name","crawler_id"}, @OA\Property(property="name", type="string"), @OA\Property(property="crawler_id", type="integer"))),
     *
     *     @OA\Response(response=201, description="Queue created", @OA\JsonContent(type="object")),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreQueueRequest $request): JsonResource
    {
        $queue = $this->repository->save($request->validated());

        return JsonResource::make($queue);
    }

    /**
     * @OA\Get(
     *     path="/queues/{id}",
     *     summary="Get single queue",
     *     description="Retrieve details of a specific queue",
     *     operationId="getQueue",
     *     tags={"Queues"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="id", in="path", description="Queue ID", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(
     *         response=200,
     *         description="The run, its jobs, and its job rollup. The seven jobs_*_count fields rode on GET /queues alone until 2026-09-07, so a run-detail view had the jobs and no counts while the list had the counts and no jobs.",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=849),
     *                 @OA\Property(
     *                     property="status_code",
     *                     type="string",
     *                     nullable=true,
     *                     enum={"running", "success", "warning", "failure", "skipped"},
     *                     description="The run's own verdict on itself. Legacy rows also hold 'failed' and 'skpped'; an unrecognised value is unknown and must never be read as success.",
     *                     example="warning"
     *                 ),
     *                 @OA\Property(property="jobs_count", type="integer", description="Every job in the run, whatever its state -- the progress denominator.", example=204),
     *                 @OA\Property(property="jobs_succeeded_count", type="integer", example=8),
     *                 @OA\Property(property="jobs_warning_count", type="integer", description="Jobs that finished but degraded. Nothing else marks them.", example=0),
     *                 @OA\Property(property="jobs_failed_count", type="integer", example=49),
     *                 @OA\Property(property="jobs_skipped_count", type="integer", example=147),
     *                 @OA\Property(property="jobs_degraded_count", type="integer", description="Counted off a three-state column: NULL is unknown and counts in neither direction, so 0 is not a clean bill of health.", example=0),
     *                 @OA\Property(property="jobs_treatment_lost_count", type="integer", description="The alarm: the bot was treated and produced control-arm data. Also three-state.", example=0),
     *                 @OA\Property(property="jobs", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Queue not found")
     * )
     */
    public function show(Queue $queue): JsonResource
    {
        $this->authorize('view', $queue);

        // The rollup and the jobs used to live on disjoint endpoints: /queues
        // carried the seven jobs_*_count and no jobs, /queues/{id} carried the
        // jobs and no counts, so a run-detail page had to fetch the whole list
        // again to put a number on the one run it was already showing. One
        // extra query, via loadCount, for the whole family.
        return JsonResource::make($queue->load(['jobs'])->loadJobOutcomeCounts());
    }

    /**
     * @OA\Get(
     *     path="/queues/job/{jobid}",
     *     summary="Get queue by job ID",
     *     description="Retrieve queue information for a specific job
     *
     **Curl Example:**
     * ```bash
     * curl -X GET 'https://pm-api.test.primemover.example/api/v1/queues/job/1' \
     * -H 'Authorization: Bearer YOUR_TOKEN' \
     * -H 'Accept: application/json'
     * ```
     *
     **R Example:**
     * ```r
     * library(httr)
     *
     * response <- GET(
     * url = ""https://pm-api.test.primemover.example/api/v1/queues/job/1"",
     * add_headers(
     * ""Authorization"" = ""Bearer YOUR_TOKEN"",
     * ""Accept"" = ""application/json""
     * )
     * )
     *
     * queue_data <- content(response, ""parsed"")
     * print(queue_data)
     * ```",
     *     operationId="getQueueByJobId",
     *     tags={"Queues"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="jobid",
     *         in="path",
     *         description="Job ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Queue information for the job",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Data Collection Queue"),
     *                 @OA\Property(property="description", type="string", example="Queue for collecting data"),
     *                 @OA\Property(property="active", type="integer", example=1),
     *                 @OA\Property(property="processed", type="integer", example=0),
     *                 @OA\Property(property="processing", type="integer", example=1),
     *                 @OA\Property(property="reviewed", type="integer", example=0),
     *                 @OA\Property(property="status_code", type="string", example="running"),
     *                 @OA\Property(property="crawler_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Job not found")
     * )
     */
    public function job($id): JsonResource
    {
        $queue = $this->repository->job($id);

        return JsonResource::make($queue);
    }

    /**
     * @OA\Get(
     *     path="/queues-processed",
     *     summary="Get processed queues",
     *     description="Retrieve all queues that have been processed
     *
     **Curl Example:**
     * ```bash
     * curl -X GET 'https://pm-api.test.primemover.example/api/v1/queues-processed' \
     * -H 'Authorization: Bearer YOUR_TOKEN' \
     * -H 'Accept: application/json'
     * ```
     *
     **R Example:**
     * ```r
     * library(httr)
     *
     * response <- GET(
     * url = ""https://pm-api.test.primemover.example/api/v1/queues-processed"",
     * add_headers(
     * ""Authorization"" = ""Bearer YOUR_TOKEN"",
     * ""Accept"" = ""application/json""
     * )
     * )
     *
     * queues_data <- content(response, ""parsed"")
     * print(queues_data)
     * ```",
     *     operationId="getProcessedQueues",
     *     tags={"Queues"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of processed queues, each carrying the seven jobs_*_count rollup fields documented on GET /queues (added 2026-09-07; before that only GET /queues had them, so showing an outcome beside a run cost a second request per run)",
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
     *                     @OA\Property(property="name", type="string", example="Processed Queue"),
     *                     @OA\Property(property="description", type="string", example="Queue that has been processed"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                     @OA\Property(property="processed", type="integer", example=1),
     *                     @OA\Property(property="processing", type="integer", example=0),
     *                     @OA\Property(property="reviewed", type="integer", example=0),
     *                     @OA\Property(property="status_code", type="string", example="completed"),
     *                     @OA\Property(property="crawler_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function processed(): ResourceCollection
    {
        $queue = $this->repository->processed();

        return ResourceCollection::make($queue);
    }

    /**
     * @OA\Get(
     *     path="/queues-unprocessed",
     *     summary="Get unprocessed queues",
     *     description="Retrieve all queues that have not been processed
     *
     **Curl Example:**
     * ```bash
     * curl -X GET 'https://pm-api.test.primemover.example/api/v1/queues-unprocessed' \
     * -H 'Authorization: Bearer YOUR_TOKEN' \
     * -H 'Accept: application/json'
     * ```
     *
     **R Example:**
     * ```r
     * library(httr)
     *
     * response <- GET(
     * url = ""https://pm-api.test.primemover.example/api/v1/queues-unprocessed"",
     * add_headers(
     * ""Authorization"" = ""Bearer YOUR_TOKEN"",
     * ""Accept"" = ""application/json""
     * )
     * )
     *
     * queues_data <- content(response, ""parsed"")
     * print(queues_data)
     * ```",
     *     operationId="getUnprocessedQueues",
     *     tags={"Queues"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of unprocessed queues, each carrying the seven jobs_*_count rollup fields documented on GET /queues (added 2026-09-07; before that only GET /queues had them, so showing an outcome beside a run cost a second request per run)",
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
     *                     @OA\Property(property="name", type="string", example="Unprocessed Queue"),
     *                     @OA\Property(property="description", type="string", example="Queue waiting to be processed"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                     @OA\Property(property="processed", type="integer", example=0),
     *                     @OA\Property(property="processing", type="integer", example=0),
     *                     @OA\Property(property="reviewed", type="integer", example=0),
     *                     @OA\Property(property="status_code", type="string", nullable=true, enum={"running", "success", "warning", "failure", "skipped"}, example="running"),
     *                     @OA\Property(property="crawler_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function unprocessed(): ResourceCollection
    {
        $queue = $this->repository->unprocessed();

        return ResourceCollection::make($queue);
    }

    /**
     * @OA\Get(
     *     path="/queues-reviewed",
     *     summary="Get reviewed queues",
     *     description="Retrieve all queues that have been reviewed
     *
     **Curl Example:**
     * ```bash
     * curl -X GET 'https://pm-api.test.primemover.example/api/v1/queues-reviewed' \
     * -H 'Authorization: Bearer YOUR_TOKEN' \
     * -H 'Accept: application/json'
     * ```
     *
     **R Example:**
     * ```r
     * library(httr)
     *
     * response <- GET(
     * url = ""https://pm-api.test.primemover.example/api/v1/queues-reviewed"",
     * add_headers(
     * ""Authorization"" = ""Bearer YOUR_TOKEN"",
     * ""Accept"" = ""application/json""
     * )
     * )
     *
     * queues_data <- content(response, ""parsed"")
     * print(queues_data)
     * ```",
     *     operationId="getReviewedQueues",
     *     tags={"Queues"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of reviewed queues, each carrying the seven jobs_*_count rollup fields documented on GET /queues (added 2026-09-07; before that only GET /queues had them, so showing an outcome beside a run cost a second request per run)",
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
     *                     @OA\Property(property="name", type="string", example="Reviewed Queue"),
     *                     @OA\Property(property="description", type="string", example="Queue that has been reviewed"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                     @OA\Property(property="processed", type="integer", example=1),
     *                     @OA\Property(property="processing", type="integer", example=0),
     *                     @OA\Property(property="reviewed", type="integer", example=1),
     *                     @OA\Property(property="status_code", type="string", example="completed"),
     *                     @OA\Property(property="crawler_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function reviewed(): ResourceCollection
    {
        $queue = $this->repository->reviewed();

        return ResourceCollection::make($queue);
    }

    /**
     * @OA\Get(
     *     path="/queues-unreviewed",
     *     summary="Get unreviewed queues",
     *     description="Retrieve all queues that have not been reviewed
     *
     **Curl Example:**
     * ```bash
     * curl -X GET 'https://pm-api.test.primemover.example/api/v1/queues-unreviewed' \
     * -H 'Authorization: Bearer YOUR_TOKEN' \
     * -H 'Accept: application/json'
     * ```
     *
     **R Example:**
     * ```r
     * library(httr)
     *
     * response <- GET(
     * url = ""https://pm-api.test.primemover.example/api/v1/queues-unreviewed"",
     * add_headers(
     * ""Authorization"" = ""Bearer YOUR_TOKEN"",
     * ""Accept"" = ""application/json""
     * )
     * )
     *
     * queues_data <- content(response, ""parsed"")
     * print(queues_data)
     * ```",
     *     operationId="getUnreviewedQueues",
     *     tags={"Queues"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of unreviewed queues, each carrying the seven jobs_*_count rollup fields documented on GET /queues (added 2026-09-07; before that only GET /queues had them, so showing an outcome beside a run cost a second request per run)",
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
     *                     @OA\Property(property="name", type="string", example="Unreviewed Queue"),
     *                     @OA\Property(property="description", type="string", example="Queue waiting for review"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                     @OA\Property(property="processed", type="integer", example=1),
     *                     @OA\Property(property="processing", type="integer", example=0),
     *                     @OA\Property(property="reviewed", type="integer", example=0),
     *                     @OA\Property(property="status_code", type="string", example="completed"),
     *                     @OA\Property(property="crawler_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function unreviewed(): ResourceCollection
    {
        $queue = $this->repository->unreviewed();

        return ResourceCollection::make($queue);
    }

    /**
     * @OA\Get(
     *     path="/experiments/{experiment}/queues-unreviewed",
     *     summary="Get unreviewed queues for experiment",
     *     description="Retrieve all unreviewed queues for a specific experiment",
     *     operationId="getExperimentUnreviewedQueues",
     *     tags={"Queues"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="experiment",
     *         in="path",
     *         description="Experiment ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of unreviewed queues for the experiment, each carrying the seven jobs_*_count rollup fields documented on GET /queues (added 2026-09-07; before that only GET /queues had them, so showing an outcome beside a run cost a second request per run)",
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
     *                     @OA\Property(property="name", type="string", example="Unreviewed Queue"),
     *                     @OA\Property(property="reviewed", type="integer", example=0),
     *                     @OA\Property(property="processed", type="integer", example=1),
     *                     @OA\Property(property="crawler_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Experiment not found")
     * )
     */
    public function experimentUnreviewed(Experiment $experiment): ResourceCollection
    {
        $queue = $this->repository->experimentUnreviewed($experiment);

        return ResourceCollection::make($queue);
    }

    /**
     * @OA\Get(
     *     path="/experiments/{experiment}/queues-reviewed",
     *     summary="Get reviewed queues for experiment",
     *     description="Retrieve all reviewed queues for a specific experiment",
     *     operationId="getExperimentReviewedQueues",
     *     tags={"Queues"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="experiment",
     *         in="path",
     *         description="Experiment ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of reviewed queues for the experiment, each carrying the seven jobs_*_count rollup fields documented on GET /queues (added 2026-09-07; before that only GET /queues had them, so showing an outcome beside a run cost a second request per run)",
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
     *                     @OA\Property(property="name", type="string", example="Reviewed Queue"),
     *                     @OA\Property(property="reviewed", type="integer", example=1),
     *                     @OA\Property(property="processed", type="integer", example=1),
     *                     @OA\Property(property="crawler_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Experiment not found")
     * )
     */
    public function experimentReviewed(Experiment $experiment): ResourceCollection
    {
        $queue = $this->repository->experimentReviewed($experiment);

        return ResourceCollection::make($queue);
    }

    /**
     * @OA\Get(
     *     path="/experiments/{experiment}/queues-unprocessed",
     *     summary="Get unprocessed queues for experiment",
     *     description="Retrieve all unprocessed queues for a specific experiment",
     *     operationId="getExperimentUnprocessedQueues",
     *     tags={"Queues"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="experiment",
     *         in="path",
     *         description="Experiment ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of unprocessed queues for the experiment, each carrying the seven jobs_*_count rollup fields documented on GET /queues (added 2026-09-07; before that only GET /queues had them, so showing an outcome beside a run cost a second request per run)",
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
     *                     @OA\Property(property="name", type="string", example="Unprocessed Queue"),
     *                     @OA\Property(property="processed", type="integer", example=0),
     *                     @OA\Property(property="processing", type="integer", example=0),
     *                     @OA\Property(property="crawler_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Experiment not found")
     * )
     */
    public function experimentUnprocessed(Experiment $experiment): ResourceCollection
    {
        $queue = $this->repository->experimentUnprocessed($experiment);

        return ResourceCollection::make($queue);
    }

    /**
     * @OA\Get(
     *     path="/experiments/{experiment}/queues-processed",
     *     summary="Get processed queues for experiment",
     *     description="Retrieve all processed queues for a specific experiment",
     *     operationId="getExperimentProcessedQueues",
     *     tags={"Queues"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="experiment",
     *         in="path",
     *         description="Experiment ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of processed queues for the experiment, each carrying the seven jobs_*_count rollup fields documented on GET /queues (added 2026-09-07; before that only GET /queues had them, so showing an outcome beside a run cost a second request per run)",
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
     *                     @OA\Property(property="name", type="string", example="Processed Queue"),
     *                     @OA\Property(property="processed", type="integer", example=1),
     *                     @OA\Property(property="processing", type="integer", example=0),
     *                     @OA\Property(property="status_code", type="string", example="completed"),
     *                     @OA\Property(property="crawler_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Experiment not found")
     * )
     */
    public function experimentProcessed(Experiment $experiment): ResourceCollection
    {
        $queue = $this->repository->experimentProcessed($experiment);

        return ResourceCollection::make($queue);
    }

    public function edit(Queue $queue): JsonResource
    {
        $this->authorize('view', $queue);

        return JsonResource::make($queue);
    }

    /**
     * @OA\Put(
     *     path="/queues/{id}",
     *     summary="Update queue",
     *     description="Update an existing queue",
     *     operationId="updateQueue",
     *     tags={"Queues"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="id", in="path", description="Queue ID", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(required=true, @OA\JsonContent(@OA\Property(property="name", type="string"), @OA\Property(property="active", type="integer"))),
     *
     *     @OA\Response(response=200, description="Queue updated", @OA\JsonContent(type="object")),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Queue not found")
     * )
     */
    public function update(UpdateQueueRequest $request, Queue $queue): JsonResource
    {
        $this->authorize('update', $queue);

        return JsonResource::make($this->repository->update($request->validated(), $queue));
    }

    /**
     * @OA\Delete(
     *     path="/queues/{id}",
     *     summary="Delete queue",
     *     description="Delete a queue",
     *     operationId="deleteQueue",
     *     tags={"Queues"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(name="id", in="path", description="Queue ID", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Queue deleted", @OA\JsonContent(type="object")),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Queue not found")
     * )
     *
     * @throws Exception
     */
    public function destroy(Queue $queue): ResourceCollection
    {
        $this->authorize('delete', $queue);

        return ResourceCollection::make($this->repository->delete($queue));
    }
}
