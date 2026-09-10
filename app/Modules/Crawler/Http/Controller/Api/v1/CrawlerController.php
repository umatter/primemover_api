<?php

namespace App\Modules\Crawler\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Crawler\Http\Request\CrawlerUploadRequest;
use App\Modules\Crawler\Http\Request\StoreCrawlerRequest;
use App\Modules\Crawler\Http\Request\UpdateCrawlerRequest;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Crawler\Repository\CrawlerRepository;
use App\Modules\Job\Model\Job;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Tests\Feature\Modules\Crawler\Http\Controller\Api\v1\CrawlerControllerTest;

/**
 * @OA\Tag(
 *     name="Crawlers",
 *     description="Web crawler management and status endpoints"
 * )
 *
 * @see CrawlerControllerTest
 */
class CrawlerController extends Controller
{
    public function __construct(protected CrawlerRepository $repository) {}

    /**
     * @OA\Get(
     *     path="/crawlers-protected",
     *     summary="Get protected crawlers",
     *     description="Retrieve all protected/active crawlers (same as main crawler index)",
     *     operationId="getProtectedCrawlers",
     *     tags={"Crawlers"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *
     *     @OA\Parameter(
     *         name="filter[status_code]",
     *         in="query",
     *         description="Which bots need attention: a comma-separated status list matched against the crawler's LATEST RUN -- the run with the greatest started_at, ties broken by id, runs that never started excluded so a session composed for tomorrow cannot mask last night's failure. Values are expanded through the alias table, so failure also matches the runs spelled 'failed'. A bot that has never run matches no status. An unrecognised value returns an empty page; an unknown filter KEY is still HTTP 400.",
     *         required=false,
     *
     *         @OA\Schema(type="string", example="failure,warning")
     *     ),
     *
     *     @OA\Parameter(
     *         name="filter[bad]",
     *         in="query",
     *         description="Shorthand for filter[status_code]=failure,warning -- the contract's is_bad predicate over the crawler's LATEST RUN, expanded through the alias table server-side so no client has to carry it. 1/true/yes/on ask for the bad ones; 0 is 'show me everything' and never 'only the good ones', because that would classify every unknown status as good.",
     *         required=false,
     *
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *
     *     @OA\Parameter(
     *         name="hours",
     *         in="query",
     *         description="Rolling window, in hours, behind every latest_queues_* count. Clamped to 1..720 rather than rejected; anything non-numeric (or absent) means the 24-hour default.",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=24, minimum=1, maximum=720)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of protected crawlers",
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
     *                     @OA\Property(property="name", type="string", example="Protected Crawler"),
     *                     @OA\Property(property="description", type="string", example="Protected crawler description"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                     @OA\Property(property="testing", type="integer", example=0),
     *                     @OA\Property(property="experiment_id", type="integer", example=1),
     *                     @OA\Property(property="latest_queues_count", type="integer", description="Runs this bot started inside the window, whatever their outcome.", example=11),
     *                     @OA\Property(property="latest_queues_succeeded", type="integer", description="Runs in the window whose own status_code is success (or a legacy synonym).", example=8),
     *                     @OA\Property(property="latest_queues_failed", type="integer", description="Runs in the window whose own status_code is failure (or the legacy 'failed'). The run produced nothing usable.", example=1),
     *                     @OA\Property(property="latest_queues_warning", type="integer", description="Runs in the window whose own status_code is warning (or the legacy 'warn'): the run completed but lost jobs along the way. Not a success -- 'needs attention' is failure OR warning.", example=2),
     *                     @OA\Property(property="latest_queues_degraded", type="integer", description="Runs in the window holding at least one job with status_code warning: job data was not written, or the decision function could not apply a filter the experiment requested. Derived from the JOBS, so a run whose own status is success can be degraded. Overlaps latest_queues_warning without containing it or being contained by it -- never add the two together.", example=1),
     *                     @OA\Property(property="latest_queues_treatment_lost", type="integer", description="Runs in the window holding at least one job whose decision_treatment_lost is true: the bot was configured to be treated and produced control-arm data anyway, so that session is scientifically void. Such a run is routinely 'success' with every job 'success' -- nothing FAILED. Counted off a three-state column, so 0 means 'no run here is KNOWN to have lost its treatment': every job predating the runner deploy has a NULL verdict.", example=0),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 @OA\Property(property="first", type="string", example="http://localhost:8000/api/v1/crawlers-protected?page=1"),
     *                 @OA\Property(property="last", type="string", example="http://localhost:8000/api/v1/crawlers-protected?page=3"),
     *                 @OA\Property(property="prev", type="string", nullable=true),
     *                 @OA\Property(property="next", type="string", example="http://localhost:8000/api/v1/crawlers-protected?page=2")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=3),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="to", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=45)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(): ResourceCollection
    {
        Crawler::useLatestQueuesWindow(request()->query('hours'));

        return ResourceCollection::make($this->repository->all());
    }

    /**
     * @OA\Get(
     *     path="/crawlers-inactive",
     *     summary="Get inactive crawlers",
     *     description="Retrieve all crawlers that are currently inactive",
     *     operationId="getInactiveCrawlers",
     *     tags={"Crawlers"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of inactive crawlers",
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
     *                     @OA\Property(property="name", type="string", example="Inactive Crawler"),
     *                     @OA\Property(property="description", type="string", example="Crawler description"),
     *                     @OA\Property(property="active", type="integer", example=0),
     *                     @OA\Property(property="testing", type="integer", example=0),
     *                     @OA\Property(property="experiment_id", type="integer", example=1),
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
    public function inactive(): ResourceCollection
    {
        $crawlers = $this->repository->inactive();

        return ResourceCollection::make($crawlers);
    }

    /**
     * @OA\Get(
     *     path="/crawlers-processed",
     *     summary="Get crawlers with processed queues",
     *     description="Retrieve all crawlers that have processed queues
     *
     **Curl Example:**
     * ```bash
     * curl -X GET 'https://pm-api.test.primemover.example/api/v1/crawlers-processed' \
     * -H 'Authorization: Bearer YOUR_TOKEN' \
     * -H 'Accept: application/json'
     * ```
     *
     **R Example:**
     * ```r
     * library(httr)
     *
     * response <- GET(
     * url = ""https://pm-api.test.primemover.example/api/v1/crawlers-processed"",
     * add_headers(
     * ""Authorization"" = ""Bearer YOUR_TOKEN"",
     * ""Accept"" = ""application/json""
     * )
     * )
     *
     * crawlers_data <- content(response, ""parsed"")
     * print(crawlers_data)
     * ```",
     *     operationId="getCrawlersWithProcessedQueues",
     *     tags={"Crawlers"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of crawlers with processed queues",
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
     *                     @OA\Property(property="name", type="string", example="Processed Crawler"),
     *                     @OA\Property(property="description", type="string", example="Crawler with processed queues"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                     @OA\Property(property="processed", type="integer", example=1),
     *                     @OA\Property(property="latest_queues_count", type="integer", description="Runs this bot started inside the window, whatever their outcome.", example=11),
     *                     @OA\Property(property="latest_queues_succeeded", type="integer", description="Runs in the window whose own status_code is success (or a legacy synonym).", example=8),
     *                     @OA\Property(property="latest_queues_failed", type="integer", description="Runs in the window whose own status_code is failure (or the legacy 'failed'). The run produced nothing usable.", example=1),
     *                     @OA\Property(property="latest_queues_warning", type="integer", description="Runs in the window whose own status_code is warning (or the legacy 'warn'): the run completed but lost jobs along the way. Not a success -- 'needs attention' is failure OR warning.", example=2),
     *                     @OA\Property(property="latest_queues_degraded", type="integer", description="Runs in the window holding at least one job with status_code warning: job data was not written, or the decision function could not apply a filter the experiment requested. Derived from the JOBS, so a run whose own status is success can be degraded. Overlaps latest_queues_warning without containing it or being contained by it -- never add the two together.", example=1),
     *                     @OA\Property(property="latest_queues_treatment_lost", type="integer", description="Runs in the window holding at least one job whose decision_treatment_lost is true: the bot was configured to be treated and produced control-arm data anyway, so that session is scientifically void. Such a run is routinely 'success' with every job 'success' -- nothing FAILED. Counted off a three-state column, so 0 means 'no run here is KNOWN to have lost its treatment': every job predating the runner deploy has a NULL verdict.", example=0),
     *                     @OA\Property(property="experiment_id", type="integer", example=1),
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
    public function withProcessedQueues(): ResourceCollection
    {
        $crawlers = $this->repository->processed();

        return ResourceCollection::make($crawlers);
    }

    /**
     * @OA\Get(
     *     path="/crawlers-reviewed",
     *     summary="Get crawlers with reviewed queues",
     *     description="Retrieve all crawlers that have reviewed queues",
     *     operationId="getCrawlersWithReviewedQueues",
     *     tags={"Crawlers"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of crawlers with reviewed queues",
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
     *                     @OA\Property(property="name", type="string", example="Reviewed Crawler"),
     *                     @OA\Property(property="description", type="string", example="Crawler with reviewed queues"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                     @OA\Property(property="reviewed", type="integer", example=1),
     *                     @OA\Property(property="latest_queues_count", type="integer", description="Runs this bot started inside the window, whatever their outcome.", example=11),
     *                     @OA\Property(property="latest_queues_succeeded", type="integer", description="Runs in the window whose own status_code is success (or a legacy synonym).", example=8),
     *                     @OA\Property(property="latest_queues_failed", type="integer", description="Runs in the window whose own status_code is failure (or the legacy 'failed'). The run produced nothing usable.", example=1),
     *                     @OA\Property(property="latest_queues_warning", type="integer", description="Runs in the window whose own status_code is warning (or the legacy 'warn'): the run completed but lost jobs along the way. Not a success -- 'needs attention' is failure OR warning.", example=2),
     *                     @OA\Property(property="latest_queues_degraded", type="integer", description="Runs in the window holding at least one job with status_code warning: job data was not written, or the decision function could not apply a filter the experiment requested. Derived from the JOBS, so a run whose own status is success can be degraded. Overlaps latest_queues_warning without containing it or being contained by it -- never add the two together.", example=1),
     *                     @OA\Property(property="latest_queues_treatment_lost", type="integer", description="Runs in the window holding at least one job whose decision_treatment_lost is true: the bot was configured to be treated and produced control-arm data anyway, so that session is scientifically void. Such a run is routinely 'success' with every job 'success' -- nothing FAILED. Counted off a three-state column, so 0 means 'no run here is KNOWN to have lost its treatment': every job predating the runner deploy has a NULL verdict.", example=0),
     *                     @OA\Property(property="experiment_id", type="integer", example=1),
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
    public function withReviewedQueues(): ResourceCollection
    {
        $crawlers = $this->repository->reviewed();

        return ResourceCollection::make($crawlers);
    }

    /**
     * @OA\Get(
     *     path="/crawlers-unprocessed",
     *     summary="Get crawlers with unprocessed queues",
     *     description="Retrieve all crawlers that have unprocessed queues
     *
     **Curl Example:**
     * ```bash
     * curl -X GET 'https://pm-api.test.primemover.example/api/v1/crawlers-unprocessed' \
     * -H 'Authorization: Bearer YOUR_TOKEN' \
     * -H 'Accept: application/json'
     * ```
     *
     **R Example:**
     * ```r
     * library(httr)
     *
     * response <- GET(
     * url = ""https://pm-api.test.primemover.example/api/v1/crawlers-unprocessed"",
     * add_headers(
     * ""Authorization"" = ""Bearer YOUR_TOKEN"",
     * ""Accept"" = ""application/json""
     * )
     * )
     *
     * crawlers_data <- content(response, ""parsed"")
     * print(crawlers_data)
     * ```
     *
     * Partitioned by runner assignment: returns only crawlers whose experiment is assigned to the calling runner account (unassigned ones go to the default runner).",
     *     operationId="getCrawlersWithUnprocessedQueues",
     *     tags={"Crawlers"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of crawlers with unprocessed queues",
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
     *                     @OA\Property(property="name", type="string", example="Unprocessed Crawler"),
     *                     @OA\Property(property="description", type="string", example="Crawler with unprocessed queues"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                     @OA\Property(property="processed", type="integer", example=0),
     *                     @OA\Property(property="latest_queues_count", type="integer", description="Runs this bot started inside the window, whatever their outcome.", example=11),
     *                     @OA\Property(property="latest_queues_succeeded", type="integer", description="Runs in the window whose own status_code is success (or a legacy synonym).", example=8),
     *                     @OA\Property(property="latest_queues_failed", type="integer", description="Runs in the window whose own status_code is failure (or the legacy 'failed'). The run produced nothing usable.", example=1),
     *                     @OA\Property(property="latest_queues_warning", type="integer", description="Runs in the window whose own status_code is warning (or the legacy 'warn'): the run completed but lost jobs along the way. Not a success -- 'needs attention' is failure OR warning.", example=2),
     *                     @OA\Property(property="latest_queues_degraded", type="integer", description="Runs in the window holding at least one job with status_code warning: job data was not written, or the decision function could not apply a filter the experiment requested. Derived from the JOBS, so a run whose own status is success can be degraded. Overlaps latest_queues_warning without containing it or being contained by it -- never add the two together.", example=1),
     *                     @OA\Property(property="latest_queues_treatment_lost", type="integer", description="Runs in the window holding at least one job whose decision_treatment_lost is true: the bot was configured to be treated and produced control-arm data anyway, so that session is scientifically void. Such a run is routinely 'success' with every job 'success' -- nothing FAILED. Counted off a three-state column, so 0 means 'no run here is KNOWN to have lost its treatment': every job predating the runner deploy has a NULL verdict.", example=0),
     *                     @OA\Property(property="experiment_id", type="integer", example=1),
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
    public function withUnprocessedQueues(): ResourceCollection
    {
        $crawlers = $this->repository->unprocessed();

        return ResourceCollection::make($crawlers);
    }

    /**
     * @OA\Get(
     *     path="/crawlers-testing",
     *     summary="Get testing crawlers",
     *     description="Retrieve all crawlers that are in testing mode. Partitioned by runner assignment: returns only crawlers whose experiment is assigned to the calling runner account (unassigned ones go to the default runner).",
     *     operationId="getTestingCrawlers",
     *     tags={"Crawlers"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of testing crawlers",
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
     *                     @OA\Property(property="name", type="string", example="Testing Crawler"),
     *                     @OA\Property(property="description", type="string", example="Crawler in testing mode"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                     @OA\Property(property="testing", type="integer", example=1),
     *                     @OA\Property(property="experiment_id", type="integer", example=1),
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
    public function testing(): ResourceCollection
    {
        $crawlers = $this->repository->testing();

        return ResourceCollection::make($crawlers);
    }

    public function create(): JsonResource
    {
        return JsonResource::make(new Crawler());
    }

    /**
     * @OA\Post(
     *     path="/crawlers",
     *     summary="Create new crawler",
     *     description="Create a new crawler instance",
     *     operationId="createCrawler",
     *     tags={"Crawlers"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name", "experiment_id"},
     *
     *             @OA\Property(property="name", type="string", example="Test Crawler"),
     *             @OA\Property(property="description", type="string", example="Crawler description"),
     *             @OA\Property(property="experiment_id", type="integer", example=1),
     *             @OA\Property(property="active", type="integer", example=1),
     *             @OA\Property(property="testing", type="integer", example=0)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Crawler created successfully",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreCrawlerRequest $request): JsonResource
    {
        $crawler = $this->repository->save($request->validated());

        return JsonResource::make($crawler);
    }

    /**
     * @OA\Get(
     *     path="/crawlers/{id}",
     *     summary="Get single crawler",
     *     description="Retrieve details of a specific crawler",
     *     operationId="getCrawler",
     *     tags={"Crawlers"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Crawler ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="hours",
     *         in="query",
     *         description="Rolling window, in hours, behind every latest_queues_* count. Clamped to 1..720 rather than rejected; anything non-numeric (or absent) means the 24-hour default.",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=24, minimum=1, maximum=720)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Crawler details",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="bot_control_01"),
     *                 @OA\Property(property="experiment_id", type="integer", example=1),
     *                 @OA\Property(property="active", type="integer", example=1),
     *                 @OA\Property(property="latest_queues_count", type="integer", description="Runs this bot started inside the window, whatever their outcome.", example=11),
     *                 @OA\Property(property="latest_queues_succeeded", type="integer", description="Runs in the window whose own status_code is success (or a legacy synonym).", example=8),
     *                 @OA\Property(property="latest_queues_failed", type="integer", description="Runs in the window whose own status_code is failure (or the legacy 'failed'). The run produced nothing usable.", example=1),
     *                 @OA\Property(property="latest_queues_warning", type="integer", description="Runs in the window whose own status_code is warning (or the legacy 'warn'): the run completed but lost jobs along the way. Not a success -- 'needs attention' is failure OR warning.", example=2),
     *                 @OA\Property(property="latest_queues_degraded", type="integer", description="Runs in the window holding at least one job with status_code warning: job data was not written, or the decision function could not apply a filter the experiment requested. Derived from the JOBS, so a run whose own status is success can be degraded. Overlaps latest_queues_warning without containing it or being contained by it -- never add the two together.", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Crawler not found")
     * )
     */
    /**
     * Every video this bot has already chosen, newest first.
     *
     * The input to the decision function's `already_watched` filter, which has
     * been an empty TO-DO since the beginning because a bot's history was not
     * answerable in one query: the chosen video lived only inside the MinIO
     * object until jobs.decision_selected_id existed.
     *
     * Scope is "selected by this bot, EVER" -- every run, every experiment --
     * which is the semantics a personalisation study wants: a bot that has seen
     * a video has seen it, and the fact does not expire at a run boundary.
     *
     * It answers 409 rather than an empty list when the column is missing. An
     * empty list is a positive claim -- "this bot has watched nothing" -- and
     * the caller would filter nothing while believing it had filtered
     * correctly. Absent is unknown, and unknown has to be visible; the runner
     * turns this into `already_watched: not_implemented`, which is what it
     * already reports today.
     */
    public function watched(Crawler $crawler): JsonResponse
    {
        if ( ! Job::hasSelectedIdColumn()) {
            return response()->json([
                'message' => 'jobs.decision_selected_id does not exist in this database; '
                    .'run the migration. An empty list would claim this bot has watched '
                    .'nothing, which is a different answer from "unknown".',
            ], ResponseAlias::HTTP_CONFLICT);
        }

        $watched = Job::query()
            ->select('jobs.decision_selected_id')
            ->join('queues', 'jobs.queue_id', '=', 'queues.id')
            ->where('queues.crawler_id', $crawler->id)
            ->whereNotNull('jobs.decision_selected_id')
            ->groupBy('jobs.decision_selected_id')
            ->orderByRaw('MAX(jobs.id) DESC')
            ->pluck('jobs.decision_selected_id');

        return response()->json(['data' => $watched]);
    }

    public function show(Crawler $crawler): JsonResource
    {
        $this->authorize('view', $crawler);

        // Route-model binding resolved this crawler -- rollup subselects and
        // all -- before the controller could read ?hours=, so the counts it
        // arrived with belong to whatever window was in force then. Set the
        // window this request asked for (absent means the 24h default) and drop
        // them; the accessors refetch all six in one aggregate.
        Crawler::useLatestQueuesWindow(request()->query('hours'));
        $crawler->forgetLatestQueueCounts();

        return JsonResource::make($this->repository->getById($crawler));
    }

    public function edit(Crawler $crawler): JsonResource
    {
        $this->authorize('view', $crawler);

        return JsonResource::make($crawler);
    }

    /**
     * @OA\Put(
     *     path="/crawlers/{id}",
     *     summary="Update crawler",
     *     description="Update an existing crawler",
     *     operationId="updateCrawler",
     *     tags={"Crawlers"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Crawler ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="name", type="string", example="Updated Crawler"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="active", type="integer", example=1),
     *             @OA\Property(property="testing", type="integer", example=0)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Crawler updated successfully",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Crawler not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateCrawlerRequest $request, Crawler $crawler): JsonResource
    {
        $this->authorize('update', $crawler);

        $crawler = $this->repository->update($request->validated(), $crawler);

        return JsonResource::make($crawler);
    }

    /**
     * @OA\Get(
     *     path="/crawlers-disable",
     *     summary="Disable production crawlers",
     *     description="Disable all production crawlers and return the updated list",
     *     operationId="disableProductionCrawlers",
     *     tags={"Crawlers"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="List of disabled production crawlers",
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
     *                     @OA\Property(property="name", type="string", example="Production Crawler"),
     *                     @OA\Property(property="description", type="string", example="Disabled production crawler"),
     *                     @OA\Property(property="active", type="integer", example=0),
     *                     @OA\Property(property="testing", type="integer", example=0),
     *                     @OA\Property(property="experiment_id", type="integer", example=1),
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
    public function disableProdCrawlers(): ResourceCollection
    {
        $crawler = $this->repository->disableProdCrawlers();

        return ResourceCollection::make($crawler);
    }

    /**
     * @OA\Delete(
     *     path="/crawlers/{crawler}/destroy",
     *     summary="Force delete crawler",
     *     description="Permanently delete a crawler and all associated data",
     *     operationId="forceDeleteCrawler",
     *     tags={"Crawlers"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="crawler",
     *         in="path",
     *         description="Crawler ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Crawler force deleted successfully",
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
     *                     @OA\Property(property="id", type="integer", example=2),
     *                     @OA\Property(property="name", type="string", example="Remaining Crawler"),
     *                     @OA\Property(property="description", type="string", example="Crawler that remains after deletion"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                     @OA\Property(property="testing", type="integer", example=0),
     *                     @OA\Property(property="experiment_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Crawler not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     *
     * @throws Exception
     */
    public function forceDelete(Crawler $crawler): ResourceCollection
    {
        $this->authorize('delete', $crawler);

        return ResourceCollection::make($this->repository->forceDelete($crawler));
    }

    /**
     * @OA\Delete(
     *     path="/crawlers/{id}",
     *     summary="Delete crawler (soft delete)",
     *     description="Soft delete a crawler (can be restored)",
     *     operationId="deleteCrawler",
     *     tags={"Crawlers"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Crawler ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Crawler deleted successfully",
     *
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Crawler not found")
     * )
     */
    public function destroy(Crawler $crawler): ResourceCollection
    {
        $this->authorize('delete', $crawler);

        return ResourceCollection::make($this->repository->delete($crawler));
    }

    /**
     * @OA\Get(
     *     path="/ip/{ip}",
     *     summary="Check IP address",
     *     description="Get information about an IP address or current request IP",
     *     operationId="checkIpAddress",
     *     tags={"Utilities"},
     *
     *     @OA\Parameter(
     *         name="ip",
     *         in="path",
     *         description="IP address to check (optional)",
     *         required=false,
     *
     *         @OA\Schema(type="string", format="ipv4", example="192.168.1.1")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="IP information",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="ip", type="string", example="192.168.1.1"),
     *             @OA\Property(property="country", type="string", example="US"),
     *             @OA\Property(property="region", type="string", example="Colorado"),
     *             @OA\Property(property="city", type="string", example="Denver")
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="IP not found"),
     *     @OA\Response(response=500, description="Error retrieving IP information")
     * )
     */
    public function ip($ip = null): JsonResponse
    {
        return $this->repository->ip($ip);
    }

    /**
     * @OA\Delete(
     *     path="/undo",
     *     summary="Undo crawler operations",
     *     description="Undo recent crawler operations and return updated crawler list",
     *     operationId="undoCrawlerOperations",
     *     tags={"Crawlers"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Crawler operations undone successfully",
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
     *                     @OA\Property(property="name", type="string", example="Restored Crawler"),
     *                     @OA\Property(property="description", type="string", example="Crawler after undo operation"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                     @OA\Property(property="testing", type="integer", example=0),
     *                     @OA\Property(property="experiment_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=400, description="No operations to undo"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function undo(): ResourceCollection
    {
        $crawlers = $this->repository->undo();

        return ResourceCollection::make($crawlers);
    }

    /**
     * @OA\Delete(
     *     path="/cleanup",
     *     summary="Cleanup crawlers",
     *     description="Clean up crawler data and return updated crawler list",
     *     operationId="cleanupCrawlers",
     *     tags={"Crawlers"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Crawlers cleaned up successfully",
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
     *                     @OA\Property(property="name", type="string", example="Cleaned Crawler"),
     *                     @OA\Property(property="description", type="string", example="Crawler after cleanup"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                     @OA\Property(property="testing", type="integer", example=0),
     *                     @OA\Property(property="experiment_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function cleanup(): ResourceCollection
    {
        $crawlers = $this->repository->cleanup();

        return ResourceCollection::make($crawlers);
    }

    /**
     * @OA\Post(
     *     path="/crawlers/{crawler}/upload",
     *     summary="Upload crawler configuration",
     *     description="Upload configuration data or file to a crawler
     *
     **Curl Example (with file):**
     * ```bash
     * curl -X POST 'https://pm-api.test.primemover.example/api/v1/crawlers/1/upload' \
     * -H 'Authorization: Bearer YOUR_TOKEN' \
     * -H 'Accept: application/json' \
     * -F 'file=@crawler_config.json'
     * ```
     *
     **Curl Example (with JSON content):**
     * ```bash
     * curl -X POST 'https://pm-api.test.primemover.example/api/v1/crawlers/1/upload' \
     * -H 'Authorization: Bearer YOUR_TOKEN' \
     * -H 'Content-Type: application/json' \
     * -H 'Accept: application/json' \
     * -d '{
     * ""content"": ""{\\""name\\"":\\""Test Crawler\\"",\\""active\\""1}""
     * }'
     * ```
     *
     **R Example (with file):**
     * ```r
     * library(httr)
     *
     * response <- POST(
     * url = ""https://pm-api.test.primemover.example/api/v1/crawlers/1/upload"",
     * add_headers(
     * ""Authorization"" = ""Bearer YOUR_TOKEN"",
     * ""Accept"" = ""application/json""
     * ),
     * body = list(file = upload_file(""crawler_config.json"")),
     * encode = ""multipart""
     * )
     *
     * upload_result <- content(response, ""parsed"")
     * print(upload_result)
     * ```",
     *     operationId="uploadCrawlerConfig",
     *     tags={"Crawlers"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="crawler",
     *         in="path",
     *         description="Crawler ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Upload configuration data or file",
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="Configuration file to upload"
     *                 ),
     *                 @OA\Property(
     *                     property="content",
     *                     type="string",
     *                     description="JSON configuration content as string"
     *                 )
     *             )
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(
     *                     property="content",
     *                     type="string",
     *                     format="json",
     *                     description="JSON configuration content",
     *                     example="{""name"":""Test Crawler"",""active"":1,""configuration"":{}}"
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Configuration uploaded successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="status", type="string", example="success"),
     *                 @OA\Property(property="message", type="string", example="Configuration uploaded successfully"),
     *                 @OA\Property(property="crawler_id", type="integer", example=1),
     *                 @OA\Property(property="uploaded_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Crawler not found"),
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
     *                     property="file",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The file field is required when content is not present.")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=500, description="Internal server error")
     * )
     *
     * @throws Exception
     */
    public function upload(CrawlerUploadRequest $request, Crawler $crawler): JsonResource
    {
        $this->authorize('update', $crawler);

        $response = $this->repository->upload($request, $crawler);

        if ($request->isResponseEnabled()) {
            return JsonResource::make($response);
        }

        return JsonResource::make([
            'status' => 'success',
            'request' => $request->all(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/load-test",
     *     summary="Load test data",
     *     description="Load test data for crawler testing purposes",
     *     operationId="loadTestData",
     *     tags={"Development"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Test data to load",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 description="Array of test data objects",
     *
     *                 @OA\Items(type="object")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Test data loaded successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Test data loaded successfully"),
     *             @OA\Property(property="loaded_count", type="integer", example=10)
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function load(\Illuminate\Http\Request $request): JsonResponse
    {
        return $this->repository->load($request);
    }
}
