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
     *     @OA\Parameter(name="sortBy", in="query", description="Sort field", required=false, @OA\Schema(type="string")),
     *
     *     @OA\Response(response=200, description="List of queues", @OA\JsonContent(type="object")),
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
     *     @OA\Response(response=200, description="Queue details", @OA\JsonContent(type="object")),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Queue not found")
     * )
     */
    public function show(Queue $queue): JsonResource
    {
        $this->authorize('view', $queue);

        return JsonResource::make($queue->load(['jobs']));
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
     *         description="List of processed queues",
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
     *         description="List of unprocessed queues",
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
     *                     @OA\Property(property="status_code", type="string", example="pending"),
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
     *         description="List of reviewed queues",
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
     *         description="List of unreviewed queues",
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
     *         description="List of unreviewed queues for the experiment",
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
     *         description="List of reviewed queues for the experiment",
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
     *         description="List of unprocessed queues for the experiment",
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
     *         description="List of processed queues for the experiment",
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
