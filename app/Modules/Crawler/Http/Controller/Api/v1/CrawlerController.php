<?php

namespace App\Modules\Crawler\Http\Controller\Api\v1;

use App\Http\Controllers\API\v1\Controller;
use App\Modules\Agent\Model\Agent;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Http\Request\StoreCrawlerRequest;
use App\Modules\Crawler\Http\Request\UpdateCrawlerRequest;
use App\Modules\Crawler\Http\Service\CrawlerService;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Proxy\Model\Proxy;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CrawlerController extends Controller
{
    protected $crawlerService;

    public function __construct(CrawlerService $crawlerService)
    {
        $this->crawlerService = $crawlerService;
    }
    
    public function index(Request $request): ResourceCollection
    {
        $experimentId = $request->input('experiment');
        $crawlers = $this->crawlerService->getData($experimentId);

        return ResourceCollection::make($crawlers);
    }

    public function inactive(): ResourceCollection
    {
        $crawlers = $this->crawlerService->inactive();

        return ResourceCollection::make($crawlers);
    }

    public function withProcessedQueues(): ResourceCollection
    {
        $crawlers = $this->crawlerService->processed();

        return ResourceCollection::make($crawlers);
    }

    public function withReviewedQueues(): ResourceCollection
    {
        $crawlers = $this->crawlerService->reviewed();

        return ResourceCollection::make($crawlers);
    }

    public function withUnprocessedQueues(): ResourceCollection
    {
        $crawlers = $this->crawlerService->unprocessed();

        return ResourceCollection::make($crawlers);
    }

    public function testing(): ResourceCollection
    {
        $crawlers = $this->crawlerService->testing();

        return ResourceCollection::make($crawlers);
    }

    public function create(): JsonResource
    {
        return JsonResource::make(new Crawler());
    }

    public function store(StoreCrawlerRequest $request): JsonResource
    {
        $crawler = $this->crawlerService->save($request->validated());

        return JsonResource::make($crawler);
    }

    public function show(Crawler $crawler): JsonResource
    {
        return JsonResource::make($this->crawlerService->getById($crawler));
    }

    public function edit(Crawler $crawler): JsonResource
    {
        return JsonResource::make($crawler);
    }

    public function update(UpdateCrawlerRequest $request, Crawler $crawler): JsonResource
    {
        $crawler = $this->crawlerService->update($request->validated(), $crawler);

        return JsonResource::make($crawler);
    }

    public function disableProdCrawlers(): ResourceCollection
    {
        $crawler = $this->crawlerService->disableProdCrawlers();

        return ResourceCollection::make($crawler);
    }

    /**
     * @throws Exception
     */
    public function forceDelete(Crawler $crawler): ResourceCollection
    {
        return ResourceCollection::make($this->crawlerService->forceDelete($crawler));
    }

    public function destroy(Crawler $crawler): ResourceCollection
    {
        return ResourceCollection::make($this->crawlerService->delete($crawler));
    }

    public function ip($ip = null): JsonResponse
    {
        return $this->crawlerService->ip($ip);
    }

    public function undo(): ResourceCollection
    {
        $crawlers = $this->crawlerService->undo();

        return ResourceCollection::make($crawlers);
    }

    public function cleanup(): ResourceCollection
    {
        $crawlers = $this->crawlerService->cleanup();

        return ResourceCollection::make($crawlers);
    }

    public function load(): JsonResponse
    {
        return $this->crawlerService->load();
    }

    public function makeCrawlers($data): JsonResponse
    {
        return $this->crawlerService->makeCrawlers($data);
    }

    public function makeQueues($data, $crawler)
    {
        $this->crawlerService->makeQueues($crawler, $data);
    }

    public function makeJobs($data, $queue)
    {
        $this->crawlerService->makeJobs($data, $queue);
    }

    public function makeBehaviors($data, $job)
    {
        $this->crawlerService->makeBehaviors($data, $job);
    }

    public function makeProxies($data, $crawler): Proxy
    {

        $new = $this->crawlerService->makeProxies($data, $crawler);

        return $new;
    }

    public function log($content)
    {
        echo '<pre>';
        print_r($content);
        echo '</pre>';
    }

    public function makeConfigurations($data, $crawler): Configuration
    {
        $new = $this->crawlerService->makeConfigurations($data, $crawler);

        return $new;
    }

    public function makePreferences($data, $model)
    {
        $this->crawlerService->makePreferences($data, $model);
    }

    public function makeAgents($data, $crawler): Agent
    {
        $new = $this->crawlerService->makeAgents($data, $crawler);

        return $new;
    }
}
