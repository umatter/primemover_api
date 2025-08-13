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
     *     path="/api/v1/crawlers-protected",
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
        $crawlers = $this->repository->all();

        return ResourceCollection::make($crawlers->paginate());
    }

    /**
     * @OA\Get(
     *     path="/api/v1/crawlers-inactive",
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
     *     path="/api/v1/crawlers-processed",
     *     summary="Get crawlers with processed queues",
     *     description="Retrieve all crawlers that have processed queues",
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
     *     path="/api/v1/crawlers-reviewed",
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
     *     path="/api/v1/crawlers-unprocessed",
     *     summary="Get crawlers with unprocessed queues",
     *     description="Retrieve all crawlers that have unprocessed queues",
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
     *     path="/api/v1/crawlers-testing",
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

    public function store(StoreCrawlerRequest $request): JsonResource
    {
        $crawler = $this->repository->save($request->validated());

        return JsonResource::make($crawler);
    }

    public function show(Crawler $crawler): JsonResource
    {
        return JsonResource::make($this->repository->getById($crawler));
    }

    public function edit(Crawler $crawler): JsonResource
    {
        return JsonResource::make($crawler);
    }

    public function update(UpdateCrawlerRequest $request, Crawler $crawler): JsonResource
    {
        $crawler = $this->repository->update($request->validated(), $crawler);

        return JsonResource::make($crawler);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/crawlers-disable",
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
     * @throws Exception
     */
    public function forceDelete(Crawler $crawler): ResourceCollection
    {
        return ResourceCollection::make($this->repository->forceDelete($crawler));
    }

    public function destroy(Crawler $crawler): ResourceCollection
    {
        return ResourceCollection::make($this->repository->delete($crawler));
    }

    /**
     * @OA\Get(
     *     path="/ip/{ip}",
     *     summary="Check IP address",
     *     description="Get information about an IP address or current request IP",
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
     *     )
     * )
     */
    public function ip($ip = null): JsonResponse
    {
        return $this->repository->ip($ip);
    }

    public function undo(): ResourceCollection
    {
        $crawlers = $this->repository->undo();

        return ResourceCollection::make($crawlers);
    }

    public function cleanup(): ResourceCollection
    {
        $crawlers = $this->repository->cleanup();

        return ResourceCollection::make($crawlers);
    }

    /**
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
}
