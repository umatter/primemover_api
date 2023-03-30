<?php

namespace App\Modules\Crawler\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Agent\Model\Agent;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Http\Request\StoreCrawlerRequest;
use App\Modules\Crawler\Http\Request\UpdateCrawlerRequest;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Crawler\Repository\CrawlerRepository;
use App\Modules\Proxy\Model\Proxy;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Tests\Feature\Modules\Crawler\Http\Controller\Api\v1\CrawlerControllerTest;

/**
 * @see CrawlerControllerTest
 */
class CrawlerController extends Controller
{
    public function __construct(protected CrawlerRepository $repository)
    {
    }

    public function index(Request $request): ResourceCollection
    {
        $experimentId = $request->input('experiment');
        $crawlers = $this->repository->getData($experimentId);

        return ResourceCollection::make($crawlers);
    }

    public function inactive(): ResourceCollection
    {
        $crawlers = $this->repository->inactive();

        return ResourceCollection::make($crawlers);
    }

    public function withProcessedQueues(): ResourceCollection
    {
        $crawlers = $this->repository->processed();

        return ResourceCollection::make($crawlers);
    }

    public function withReviewedQueues(): ResourceCollection
    {
        $crawlers = $this->repository->reviewed();

        return ResourceCollection::make($crawlers);
    }

    public function withUnprocessedQueues(): ResourceCollection
    {
        $crawlers = $this->repository->unprocessed();

        return ResourceCollection::make($crawlers);
    }

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

    public function load(): JsonResponse
    {
        return $this->repository->load();
    }

    public function makeCrawlers($data): JsonResponse
    {
        return $this->repository->makeCrawlers($data);
    }

    public function makeQueues($data, $crawler): void
    {
        $this->repository->makeQueues($crawler, $data);
    }

    public function makeJobs($data, $queue): void
    {
        $this->repository->makeJobs($data, $queue);
    }

    public function makeBehaviors($data, $job): void
    {
        $this->repository->makeBehaviors($data, $job);
    }

    public function makeProxies($data, $crawler): Proxy
    {
        return $this->repository->makeProxies($data, $crawler);
    }

    public function log($content): void
    {
        echo '<pre>';
        print_r($content);
        echo '</pre>';
    }

    public function makeConfigurations($data, $crawler): Configuration
    {
        return $this->repository->makeConfigurations($data, $crawler);
    }

    public function makePreferences($data, $model): void
    {
        $this->repository->makePreferences($data, $model);
    }

    public function makeAgents($data, $crawler): Agent
    {
        return $this->repository->makeAgents($data, $crawler);
    }
}
