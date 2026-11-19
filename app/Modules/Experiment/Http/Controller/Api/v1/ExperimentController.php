<?php

namespace App\Modules\Experiment\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Facade\ExperimentRepository;
use App\Modules\Experiment\Http\Request\ExperimentStoreRequest;
use App\Modules\Experiment\Http\Request\ExperimentUploadRequest;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Experiment\Service\ExperimentHealth;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;

/**
 * @OA\Tag(
 *     name="Experiments",
 *     description="Experiment management and data handling endpoints"
 * )
 */
class ExperimentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/experiments",
     *     summary="List experiments (paginated)",
     *     description="Retrieve a paginated list of experiments with pagination support",
     *     operationId="getExperiments",
     *     tags={"Experiments"},
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
     *         name="hours",
     *         in="query",
     *         description="Rolling window, in hours, behind runs_bad_count -- the same window and the same clamping as /crawlers, so a bot's latest_queues_* and its study's runs_bad_count always describe the same stretch of time. Clamped to 1..720 rather than rejected; anything non-numeric (or absent) means the 24-hour default.",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=24, minimum=1, maximum=720)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of experiments, each carrying runs_bad_count",
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
     *                     @OA\Property(property="name", type="string", example="Test Experiment"),
     *                     @OA\Property(property="description", type="string", example="Experiment description"),
     *                     @OA\Property(property="contact", type="string", example="researcher@example.com"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="runner_user_id", type="integer", nullable=true, example=1, description="Runner account that executes this experiment; null = default runner"),
     *                     @OA\Property(property="runs_bad_count", type="integer", description="Runs of this study, in the rolling window, that need a human: status_code in failure OR warning, legacy spellings ('failed', 'error', 'fatal', 'warn') included. This is the contract's is_bad predicate (docs/integration-contract.md 3.4) and never failure alone -- a run that completed having lost work is precisely the one nobody would otherwise look at. Use GET /experiments/{id}/health for the full breakdown.", example=3),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 @OA\Property(property="first", type="string"),
     *                 @OA\Property(property="last", type="string"),
     *                 @OA\Property(property="prev", type="string", nullable=true),
     *                 @OA\Property(property="next", type="string", nullable=true)
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="from", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="to", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(): ResourceCollection
    {
        // Before the query is built: runs_bad_count is a count subselect and
        // the window is read while Eloquent assembles it.
        Crawler::useLatestQueuesWindow(request()->query('hours'));

        $experiments = ExperimentRepository::getData();

        return ResourceCollection::make($experiments->paginate());
    }

    /**
     * @OA\Get(
     *     path="/experiments/experiments-list",
     *     summary="Get all experiments list",
     *     description="Retrieve a complete list of all experiments without pagination
     *
     **Curl Example:**
     * ```bash
     * curl -X GET 'https://pm-api.test.primemover.example/api/v1/experiments/experiments-list' \
     * -H 'Authorization: Bearer YOUR_TOKEN' \
     * -H 'Accept: application/json'
     * ```
     *
     **R Example:**
     * ```r
     * library(httr)
     *
     * response <- GET(
     * url = ""https://pm-api.test.primemover.example/api/v1/experiments/experiments-list"",
     * add_headers(
     * ""Authorization"" = ""Bearer YOUR_TOKEN"",
     * ""Accept"" = ""application/json""
     * )
     * )
     *
     * experiments_data <- content(response, ""parsed"")
     * print(experiments_data)
     * ```",
     *     operationId="getAllExperimentsList",
     *     tags={"Experiments"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Complete list of experiments",
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
     *                     @OA\Property(property="name", type="string", example="Test-Experiment 1"),
     *                     @OA\Property(property="description", type="string", example="Lorem ipsum dolor sit amet"),
     *                     @OA\Property(property="contact", type="string", example="Nobody"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                     @OA\Property(property="updated_at", type="string", format="date-time"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="user_id", type="integer", example=2),
     *                     @OA\Property(property="runner_user_id", type="integer", nullable=true, example=2, description="Runner account that executes this experiment; null = default runner")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function all(): ResourceCollection
    {
        $experiments = ExperimentRepository::all();

        return ResourceCollection::make($experiments);
    }

    /**
     * @OA\Post(
     *     path="/experiments",
     *     summary="Create new experiment",
     *     description="Create a new experiment with name, description, and contact information",
     *     operationId="createExperiment",
     *     tags={"Experiments"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name"},
     *
     *             @OA\Property(property="name", type="string", example="My New Experiment", description="Experiment name"),
     *             @OA\Property(property="description", type="string", example="Research project description", description="Experiment description"),
     *             @OA\Property(property="contact", type="string", example="researcher@example.com", description="Contact information"),
     *             @OA\Property(property="active", type="integer", example=1, description="Active status (0 or 1)"),
     *             @OA\Property(property="runner_user_id", type="integer", nullable=true, example=1, description="Runner account that executes this experiment; null = default runner")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Experiment created successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="My New Experiment"),
     *                 @OA\Property(property="description", type="string", example="Research project description"),
     *                 @OA\Property(property="contact", type="string", example="researcher@example.com"),
     *                 @OA\Property(property="active", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="runner_user_id", type="integer", nullable=true, example=1, description="Runner account that executes this experiment; null = default runner"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
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
     *                     property="name",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The name field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     *
     * @throws Exception
     */
    public function store(ExperimentStoreRequest $request): JsonResource
    {
        $experiment = ExperimentRepository::save($request);

        return JsonResource::make($experiment);
    }

    /**
     * @OA\Get(
     *     path="/experiments/{id}",
     *     summary="Get single experiment",
     *     description="Retrieve details of a specific experiment including associated crawlers",
     *     operationId="getExperiment",
     *     tags={"Experiments"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Experiment ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="hours",
     *         in="query",
     *         description="Rolling window, in hours, behind runs_bad_count. Clamped to 1..720; anything non-numeric (or absent) means the 24-hour default.",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=24, minimum=1, maximum=720)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Experiment details",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Test Experiment"),
     *                 @OA\Property(property="description", type="string", example="Experiment description"),
     *                 @OA\Property(property="contact", type="string", example="researcher@example.com"),
     *                 @OA\Property(property="active", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="runner_user_id", type="integer", nullable=true, example=1, description="Runner account that executes this experiment; null = default runner"),
     *                 @OA\Property(property="runs_bad_count", type="integer", description="Runs of this study, in the rolling window, whose status_code is failure OR warning (legacy spellings included). The contract's is_bad predicate; never failure alone.", example=3),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(
     *                     property="crawlers",
     *                     type="array",
     *                     description="Associated crawlers",
     *
     *                     @OA\Items(type="object")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Experiment not found")
     * )
     */
    public function show(Experiment $experiment): JsonResource
    {
        $this->authorize('view', $experiment);

        // Route-model binding resolved the row before ?hours= could be read;
        // runs_bad_count is loaded afterwards (getById), so setting the window
        // here is enough -- there is nothing stale to discard.
        Crawler::useLatestQueuesWindow(request()->query('hours'));

        return JsonResource::make(ExperimentRepository::getById($experiment));
    }

    public function edit(Experiment $experiment): JsonResource
    {
        $this->authorize('view', $experiment);

        return JsonResource::make($experiment);
    }

    /**
     * @OA\Get(
     *     path="/experiments/{experiment}/health",
     *     summary="Health of one experiment over a rolling window",
     *     description="Triage above the bot. An experiment carries no status of its own -- no column, no aggregate -- so answering 'which of my studies needs attention?' used to mean paging through every crawler (15 per page) and summing the rollups client-side. This is that answer in one request and a constant number of grouped queries.
     *
     * Three things worth knowing before reading the numbers:
     *
     * - **Every bucket is alias-aware.** status_code is not a normalised column and the API deliberately never coerces on write, so the same outcome is spelled several ways in one table: 277 queue rows and 256 job rows say failed where the runner now says failure, and skpped is a runner typo that persists. Rows are grouped by the raw value and folded through the alias table, so failure counts all four spellings.
     * - **unknown is a reported bucket, never folded into success.** A NULL status_code, an empty one, and a spelling nobody has an alias for all land there. The six buckets always sum to total.
     * - **bad_runs / bad_jobs are failure OR warning** (docs/integration-contract.md 3.4), never failure alone. warning means the run or job finished having lost something the experiment cares about -- the state nobody would otherwise look at, and the reason the 2026-08 overhaul exists.
     *
     * treatment_lost_jobs and degraded_jobs count VERDICTS off three-state columns (true / false / NULL = unknown). Only true is counted, so 0 means 'nothing here is KNOWN to have lost its treatment' -- a study whose jobs all predate the runner deploy reports 0 while knowing nothing. See docs/integration-contract.md 7.3.",
     *     operationId="getExperimentHealth",
     *     tags={"Experiments"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="experiment",
     *         in="path",
     *         description="Experiment ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=30)
     *     ),
     *
     *     @OA\Parameter(
     *         name="hours",
     *         in="query",
     *         description="Size of the rolling window, counted back from now over the runs' started_at. Clamped to 1..720 rather than rejected -- this answers a dashboard, and a 400 because someone typed hours=0 would be worse than the one-hour window they nearly asked for. Anything non-numeric (or absent) means 24. The window actually used comes back as window_hours.",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=24, minimum=1, maximum=720)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Health of the experiment over the window",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="experiment_id", type="integer", example=30),
     *                 @OA\Property(property="window_hours", type="integer", description="The window actually used, after clamping.", example=24),
     *                 @OA\Property(property="bots", type="integer", description="Crawlers configured on this experiment. Deliberately NOT window-scoped: it is the denominator that makes the run counts readable.", example=12),
     *                 @OA\Property(
     *                     property="runs",
     *                     type="object",
     *                     description="Runs that STARTED inside the window, bucketed by status_code. started_at rather than created_at, because runs are composed ahead of time -- a queue exists, with a future start_at and no status_code, long before it runs.",
     *                     @OA\Property(property="total", type="integer", description="The sum of the six buckets below, never counted separately.", example=20),
     *                     @OA\Property(property="running", type="integer", example=1),
     *                     @OA\Property(property="success", type="integer", example=12),
     *                     @OA\Property(property="warning", type="integer", description="Completed, but lost work along the way.", example=3),
     *                     @OA\Property(property="failure", type="integer", example=2),
     *                     @OA\Property(property="skipped", type="integer", example=1),
     *                     @OA\Property(property="unknown", type="integer", description="Anything that normalises to none of the five: NULL, empty, or an unrecognised spelling. Reported, never counted as success.", example=1)
     *                 ),
     *                 @OA\Property(
     *                     property="jobs",
     *                     type="object",
     *                     description="Jobs belonging to those runs, bucketed the same way. Scoped through the run's start, not the job's own, so runs and jobs describe the same body of work.",
     *                     @OA\Property(property="total", type="integer", example=812),
     *                     @OA\Property(property="running", type="integer", example=4),
     *                     @OA\Property(property="success", type="integer", example=701),
     *                     @OA\Property(property="warning", type="integer", example=25),
     *                     @OA\Property(property="failure", type="integer", example=12),
     *                     @OA\Property(property="skipped", type="integer", example=64),
     *                     @OA\Property(property="unknown", type="integer", example=6)
     *                 ),
     *                 @OA\Property(property="bad_runs", type="integer", description="runs.failure + runs.warning.", example=5),
     *                 @OA\Property(property="bad_jobs", type="integer", description="jobs.failure + jobs.warning.", example=37),
     *                 @OA\Property(property="treatment_lost_jobs", type="integer", description="Jobs whose decision_treatment_lost is true: the bot was configured to be treated and produced control-arm data, so that session is scientifically void. The alarm. Such a job is routinely status_code success -- nothing about it failed.", example=4),
     *                 @OA\Property(property="degraded_jobs", type="integer", description="Jobs whose decision_degraded is true: a filter the crawler REQUESTED did not apply. 'A human should look at this decision.'", example=9),
     *                 @OA\Property(property="bots_not_starting", type="array", description="Bots whose latest finished run in the window never started: it ended 'failure' and none of its jobs ran. One entry per bot, longest streak first; empty when every bot's latest run started.",
     *
     *                     @OA\Items(type="object",
     *
     *                         @OA\Property(property="crawler_id", type="integer", example=2029),
     *                         @OA\Property(property="name", type="string", nullable=true, example="pilot_24"),
     *                         @OA\Property(property="consecutive", type="integer", description="Never-started runs back from the bot's latest finished run to its last run that did start, inside the window.", example=5),
     *                         @OA\Property(property="since", type="string", nullable=true, description="started_at of the first run of that streak inside the window."),
     *                         @OA\Property(property="latest_run_id", type="integer"),
     *                         @OA\Property(property="reason", type="string", nullable=true, description="status_message of the latest of those runs.")
     *                     )
     *                 ),
     *                 @OA\Property(property="last_finished_at", type="string", format="date-time", nullable=true, description="When this study last finished a run, ACROSS ALL TIME -- the one field here that is deliberately not window-scoped, because it is what makes an empty window readable: 'last finished 5 days ago' and 'never ran' are different situations. null means no run has ever finished.", example="2026-09-07T08:12:00.000000Z")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=403, description="Not your experiment"),
     *     @OA\Response(response=404, description="Experiment not found")
     * )
     */
    public function health(Experiment $experiment, ExperimentHealth $health): JsonResponse
    {
        // The same policy as show(): if you may see the study, you may see what
        // its runs came to.
        $this->authorize('view', $experiment);

        return response()->json([
            'data' => $health->for($experiment, request()->query('hours')),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/experiments/{id}",
     *     summary="Update experiment",
     *     description="Update an existing experiment with new information",
     *     operationId="updateExperiment",
     *     tags={"Experiments"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Experiment ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"name"},
     *
     *             @OA\Property(property="name", type="string", example="Updated Experiment Name"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="contact", type="string", example="newemail@example.com"),
     *             @OA\Property(property="active", type="integer", example=1)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Experiment updated successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Updated Experiment Name"),
     *                 @OA\Property(property="description", type="string", example="Updated description"),
     *                 @OA\Property(property="contact", type="string", example="newemail@example.com"),
     *                 @OA\Property(property="active", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="runner_user_id", type="integer", nullable=true, example=1, description="Runner account that executes this experiment; null = default runner"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Experiment not found"),
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
     *                     property="name",
     *                     type="array",
     *
     *                     @OA\Items(type="string", example="The name field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     *
     * @throws Exception
     */
    public function update(ExperimentStoreRequest $request, Experiment $experiment): JsonResource
    {
        $this->authorize('update', $experiment);

        return JsonResource::make(ExperimentRepository::update($request, $experiment));
    }

    /**
     * @OA\Delete(
     *     path="/experiments/{id}",
     *     summary="Delete experiment (soft delete)",
     *     description="Soft delete an experiment which can be restored later",
     *     operationId="deleteExperiment",
     *     tags={"Experiments"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Experiment ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Experiment deleted successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="status", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Experiment not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     *
     * @throws Exception
     */
    public function destroy(Experiment $experiment): JsonResource
    {
        $this->authorize('delete', $experiment);

        return JsonResource::make([
            'status' => ExperimentRepository::delete($experiment),
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/experiments/{experiment}/destroy",
     *     summary="Force delete experiment",
     *     description="Permanently delete an experiment and all associated data",
     *     operationId="forceDeleteExperiment",
     *     tags={"Experiments"},
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
     *         description="Experiment force deleted successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="status", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Experiment not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     *
     * @throws Exception
     */
    public function forceDelete(Experiment $experiment): JsonResource
    {
        $this->authorize('delete', $experiment);

        return JsonResource::make([
            'status' => ExperimentRepository::forceDelete($experiment),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/experiments/{experiment}/upload",
     *     summary="Upload experiment configuration",
     *     description="Upload configuration data or file to an experiment
     *
     **Curl Example (with file):**
     * ```bash
     * curl -X POST 'https://pm-api.test.primemover.example/api/v1/experiments/1/upload' \
     * -H 'Authorization: Bearer YOUR_TOKEN' \
     * -H 'Accept: application/json' \
     * -F 'file=@config.json'
     * ```
     *
     **Curl Example (with JSON content):**
     * ```bash
     * curl -X POST 'https://pm-api.test.primemover.example/api/v1/experiments/1/upload' \
     * -H 'Authorization: Bearer YOUR_TOKEN' \
     * -H 'Content-Type: application/json' \
     * -H 'Accept: application/json' \
     * -d '{
     * ""content"": ""{\\""name\\"":\\""Test Config\\"",\\""active\\""1}""
     * }'
     * ```
     *
     **R Example (with file):**
     * ```r
     * library(httr)
     *
     * response <- POST(
     * url = ""https://pm-api.test.primemover.example/api/v1/experiments/1/upload"",
     * add_headers(
     * ""Authorization"" = ""Bearer YOUR_TOKEN"",
     * ""Accept"" = ""application/json""
     * ),
     * body = list(file = upload_file(""config.json"")),
     * encode = ""multipart""
     * )
     *
     * config_result <- content(response, ""parsed"")
     * print(config_result)
     * ```",
     *     operationId="uploadExperimentConfig",
     *     tags={"Experiments"},
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
     *     @OA\RequestBody(
     *         required=true,
     *         description="Upload experiment configuration data or file",
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
     *                     description="JSON configuration content"
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
     *                 @OA\Property(
     *                     property="crawlers",
     *                     type="array",
     *                     description="Updated crawlers with configuration",
     *
     *                     @OA\Items(type="object")
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Experiment not found"),
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
     */
    /**
     * @OA\Post(
     *     path="/experiments/upload",
     *     deprecated=true,
     *     summary="Upload experiment data",
     *     description="Upload experiment configuration data or file (general upload)
     *
     **Curl Example (with file):**
     * ```bash
     * curl -X POST 'https://pm-api.test.primemover.example/api/v1/experiments/upload' \
     * -H 'Authorization: Bearer YOUR_TOKEN' \
     * -H 'Accept: application/json' \
     * -F 'file=@experiment_data.json'
     * ```
     *
     **Curl Example (with JSON content):**
     * ```bash
     * curl -X POST 'https://pm-api.test.primemover.example/api/v1/experiments/upload' \
     * -H 'Authorization: Bearer YOUR_TOKEN' \
     * -H 'Content-Type: application/json' \
     * -H 'Accept: application/json' \
     * -d '{
     * ""content"": ""{\\""name\\"":\\""Test Experiment\\"",\\""active\\""1}""
     * }'
     * ```
     *
     **R Example (with file):**
     * ```r
     * library(httr)
     *
     * response <- POST(
     * url = ""https://pm-api.test.primemover.example/api/v1/experiments/upload"",
     * add_headers(
     * ""Authorization"" = ""Bearer YOUR_TOKEN"",
     * ""Accept"" = ""application/json""
     * ),
     * body = list(file = upload_file(""experiment_data.json"")),
     * encode = ""multipart""
     * )
     *
     * upload_result <- content(response, ""parsed"")
     * print(upload_result)
     * ```",
     *     operationId="uploadExperimentData",
     *     tags={"Experiments"},
     *     security={{"bearerAuth": {}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="Upload experiment data or file",
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
     *                     description="Experiment data file to upload"
     *                 ),
     *                 @OA\Property(
     *                     property="content",
     *                     type="string",
     *                     description="JSON experiment data as string"
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
     *                     description="JSON experiment data"
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Experiment data uploaded successfully",
     *
     *         @OA\JsonContent(
     *             type="object",
     *
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="status", type="string", example="success"),
     *                 @OA\Property(property="message", type="string", example="Experiment data uploaded successfully"),
     *                 @OA\Property(property="uploaded_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
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
     */
    /**
     * @OA\Post(
     *     path="/experiments/{experiment}/upload",
     *     summary="Upload experiment data",
     *     operationId="uploadExperimentDataById",
     *     tags={"Experiments"},
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
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="file", type="string", format="binary"),
     *                 @OA\Property(property="content", type="string")
     *             )
     *         ),
     *
     *         @OA\MediaType(
     *             mediaType="application/json",
     *
     *             @OA\Schema(
     *                 type="object",
     *
     *                 @OA\Property(property="content", type="string")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Upload successful"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Experiment not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function upload(ExperimentUploadRequest $request, ?Experiment $experiment = null): JsonResource
    {
        $uploadResult = ExperimentRepository::upload($request, $experiment);

        if ($request->isResponseEnabled()) {
            return JsonResource::make([
                'crawlers' => Crawler::query()
                    ->where('updated', 1)
                    ->with(['proxy', 'agent.preferences', 'configuration.preferences', 'queuesNew'])
                    ->get(),
            ]);
        }

        return JsonResource::make($uploadResult);
    }

    /**
     * @OA\Get(
     *     path="/experiments/{experiment}/download",
     *     summary="Download experiment data",
     *     description="Download experiment data as a file (ZIP archive)
     *
     **Curl Example:**
     * ```bash
     * curl -X GET 'https://pm-api.test.primemover.example/api/v1/experiments/1/download' \
     * -H 'Authorization: Bearer YOUR_TOKEN' \
     * -H 'Accept: application/zip' \
     * -o experiment_1_data.zip
     * ```
     *
     **R Example:**
     * ```r
     * library(httr)
     *
     * response <- GET(
     * url = ""https://pm-api.test.primemover.example/api/v1/experiments/1/download"",
     * add_headers(
     * ""Authorization"" = ""Bearer YOUR_TOKEN"",
     * ""Accept"" = ""application/zip""
     * )
     * )
     *
     * # Save the file
     * writeBin(content(response, ""raw""), ""experiment_1_data.zip"")
     * print(""File downloaded successfully"")
     * ```",
     *     operationId="downloadExperimentData",
     *     tags={"Experiments"},
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
     *         description="File download initiated",
     *
     *         @OA\MediaType(
     *             mediaType="application/zip",
     *
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Experiment not found or no data available"),
     *     @OA\Response(response=500, description="Error generating download file")
     * )
     */
    public function download(Experiment $experiment): Response
    {
        $this->authorize('view', $experiment);

        return ExperimentRepository::download($experiment);
    }
}
