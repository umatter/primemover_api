<?php

namespace App\Modules\Crawler\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Crawler\Http\Request\CrawlerUploadRequest;
use App\Modules\Crawler\Http\Request\StoreCrawlerRequest;
use App\Modules\Crawler\Http\Request\UpdateCrawlerRequest;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Crawler\Repository\CrawlerRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
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
    public function __construct(protected CrawlerRepository $repository)
    {
    }

    /**
     * @OA\Get(
     *     path="/crawlers-protected",
     *     summary="Get protected crawlers",
     *     description="Retrieve all protected/active crawlers (same as main crawler index)",
     *     operationId="getProtectedCrawlers",
     *     tags={"Crawlers"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paginated list of protected crawlers",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Protected Crawler"),
     *                     @OA\Property(property="description", type="string", example="Protected crawler description"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                     @OA\Property(property="testing", type="integer", example=0),
     *                     @OA\Property(property="experiment_id", type="integer", example=1),
     *                     @OA\Property(property="latest_queues_succeeded", type="integer", example=10),
     *                     @OA\Property(property="latest_queues_failed", type="integer", example=1),
     *                     @OA\Property(property="latest_queues_count", type="integer", example=11),
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
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(): ResourceCollection
    {
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
     *     @OA\Response(
     *         response=200,
     *         description="List of inactive crawlers",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
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

**Curl Example:**
```bash
curl -X GET 'https://pm-api.test.primemover.example/api/v1/crawlers-processed' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Accept: application/json'
```

**R Example:**
```r
library(httr)

response <- GET(
  url = ""https://pm-api.test.primemover.example/api/v1/crawlers-processed"",
  add_headers(
    ""Authorization"" = ""Bearer YOUR_TOKEN"",
    ""Accept"" = ""application/json""
  )
)

crawlers_data <- content(response, ""parsed"")
print(crawlers_data)
```",
     *     operationId="getCrawlersWithProcessedQueues",
     *     tags={"Crawlers"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of crawlers with processed queues",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Processed Crawler"),
     *                     @OA\Property(property="description", type="string", example="Crawler with processed queues"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                     @OA\Property(property="processed", type="integer", example=1),
     *                     @OA\Property(property="latest_queues_succeeded", type="integer", example=5),
     *                     @OA\Property(property="latest_queues_failed", type="integer", example=0),
     *                     @OA\Property(property="experiment_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
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
     *     @OA\Response(
     *         response=200,
     *         description="List of crawlers with reviewed queues",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Reviewed Crawler"),
     *                     @OA\Property(property="description", type="string", example="Crawler with reviewed queues"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                     @OA\Property(property="reviewed", type="integer", example=1),
     *                     @OA\Property(property="latest_queues_count", type="integer", example=3),
     *                     @OA\Property(property="experiment_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
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

**Curl Example:**
```bash
curl -X GET 'https://pm-api.test.primemover.example/api/v1/crawlers-unprocessed' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Accept: application/json'
```

**R Example:**
```r
library(httr)

response <- GET(
  url = ""https://pm-api.test.primemover.example/api/v1/crawlers-unprocessed"",
  add_headers(
    ""Authorization"" = ""Bearer YOUR_TOKEN"",
    ""Accept"" = ""application/json""
  )
)

crawlers_data <- content(response, ""parsed"")
print(crawlers_data)
```",
     *     operationId="getCrawlersWithUnprocessedQueues",
     *     tags={"Crawlers"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of crawlers with unprocessed queues",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Unprocessed Crawler"),
     *                     @OA\Property(property="description", type="string", example="Crawler with unprocessed queues"),
     *                     @OA\Property(property="active", type="integer", example=1),
     *                     @OA\Property(property="processed", type="integer", example=0),
     *                     @OA\Property(property="latest_queues_count", type="integer", example=2),
     *                     @OA\Property(property="experiment_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
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
     *     description="Retrieve all crawlers that are in testing mode",
     *     operationId="getTestingCrawlers",
     *     tags={"Crawlers"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of testing crawlers",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
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
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "experiment_id"},
     *             @OA\Property(property="name", type="string", example="Test Crawler"),
     *             @OA\Property(property="description", type="string", example="Crawler description"),
     *             @OA\Property(property="experiment_id", type="integer", example=1),
     *             @OA\Property(property="active", type="integer", example=1),
     *             @OA\Property(property="testing", type="integer", example=0)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Crawler created successfully",
     *         @OA\JsonContent(type="object")
     *     ),
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
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Crawler ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Crawler details",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Crawler not found")
     * )
     */
    public function show(Crawler $crawler): JsonResource
    {
        return JsonResource::make($this->repository->getById($crawler));
    }

    public function edit(Crawler $crawler): JsonResource
    {
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
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Crawler ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Crawler"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="active", type="integer", example=1),
     *             @OA\Property(property="testing", type="integer", example=0)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Crawler updated successfully",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Crawler not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(UpdateCrawlerRequest $request, Crawler $crawler): JsonResource
    {
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
     *     @OA\Response(
     *         response=200,
     *         description="List of disabled production crawlers",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
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
     *     @OA\Parameter(
     *         name="crawler",
     *         in="path",
     *         description="Crawler ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Crawler force deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
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
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Crawler not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     * 
     * @throws Exception
     */
    public function forceDelete(Crawler $crawler): ResourceCollection
    {
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
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Crawler ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Crawler deleted successfully",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Crawler not found")
     * )
     */
    public function destroy(Crawler $crawler): ResourceCollection
    {
        return ResourceCollection::make($this->repository->delete($crawler));
    }

    /**
     * @OA\Get(
     *     path="/ip/{ip}",
     *     summary="Check IP address",
     *     description="Get information about an IP address or current request IP",
     *     operationId="checkIpAddress",
     *     tags={"Utilities"},
     *     @OA\Parameter(
     *         name="ip",
     *         in="path",
     *         description="IP address to check (optional)",
     *         required=false,
     *         @OA\Schema(type="string", format="ipv4", example="192.168.1.1")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="IP information",
     *         @OA\JsonContent(
     *             @OA\Property(property="ip", type="string", example="192.168.1.1"),
     *             @OA\Property(property="country", type="string", example="US"),
     *             @OA\Property(property="region", type="string", example="Colorado"),
     *             @OA\Property(property="city", type="string", example="Denver")
     *         )
     *     ),
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
     *     @OA\Response(
     *         response=200,
     *         description="Crawler operations undone successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
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
     *     @OA\Response(
     *         response=200,
     *         description="Crawlers cleaned up successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
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

**Curl Example (with file):**
```bash
curl -X POST 'https://pm-api.test.primemover.example/api/v1/crawlers/1/upload' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Accept: application/json' \
  -F 'file=@crawler_config.json'
```

**Curl Example (with JSON content):**
```bash
curl -X POST 'https://pm-api.test.primemover.example/api/v1/crawlers/1/upload' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{
    ""content"": ""{\\""name\\"":\\""Test Crawler\\"",\\""active\\""1}""
  }'
```

**R Example (with file):**
```r
library(httr)

response <- POST(
  url = ""https://pm-api.test.primemover.example/api/v1/crawlers/1/upload"",
  add_headers(
    ""Authorization"" = ""Bearer YOUR_TOKEN"",
    ""Accept"" = ""application/json""
  ),
  body = list(file = upload_file(""crawler_config.json"")),
  encode = ""multipart""
)

upload_result <- content(response, ""parsed"")
print(upload_result)
```",
     *     operationId="uploadCrawlerConfig",
     *     tags={"Crawlers"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="crawler",
     *         in="path",
     *         description="Crawler ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Upload configuration data or file",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
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
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
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
     *     @OA\Response(
     *         response=200,
     *         description="Configuration uploaded successfully",
     *         @OA\JsonContent(
     *             type="object",
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
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Crawler not found"),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="file",
     *                     type="array",
     *                     @OA\Items(type="string", example="The file field is required when content is not present.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     * 
     * @throws Exception
     */
    public function upload(CrawlerUploadRequest $request, Crawler $crawler): JsonResource
    {
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
     *     @OA\RequestBody(
     *         required=true,
     *         description="Test data to load",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 description="Array of test data objects",
     *                 @OA\Items(type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test data loaded successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Test data loaded successfully"),
     *             @OA\Property(property="loaded_count", type="integer", example=10)
     *         )
     *     ),
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
