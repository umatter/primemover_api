<?php

namespace App\Modules\Experiment\Http\Controller\Api\v1;

use App\Facades\Services\PreferenceService;
use App\Http\Controllers\API\v1\Controller;
use App\Modules\Configuration\Http\Request\ConfigurationStoreRequest;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Http\Request\StoreCrawlerRequest;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Http\Request\ExperimentStoreRequest;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Job\Http\Request\StoreJobRequest;
use App\Modules\Job\Model\Job;
use App\Modules\Proxy\Http\Request\ProxyIndexRequest;
use App\Modules\Proxy\Model\Proxy;
use App\Modules\Queue\Http\Request\StoreQueueRequest;
use App\Modules\Queue\Model\Queue;
use Exception;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ExperimentController extends Controller
{
    public function index(): ResourceCollection
    {
        $experiments = QueryBuilder::for(Experiment::class)
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
            ])
            ->latest()
            ->fastPaginate();

        return ResourceCollection::make($experiments);
    }

    public function all(): ResourceCollection
    {
        $experiments = Experiment::query()
            ->latest()
            ->select(['id', 'name'])
            ->get();

        return ResourceCollection::make($experiments);
    }

    /**
     * @throws Exception
     */
    public function store(ExperimentStoreRequest $request): JsonResource
    {
        $experiment = Experiment::query()->create($request->validated());

        if ($request->getPreferences()) {
            PreferenceService::createManyForModel($request->getPreferences(), $experiment);
        }

        return JsonResource::make($experiment);
    }

    public function show(Experiment $experiment): JsonResource
    {
        return JsonResource::make(
            $experiment->load([
                'crawlers.agent',
                'crawlers.proxy',
                'crawlers.configuration',
                'crawlers.configuration.preferences',
                'preferences',
            ])
        );
    }

    public function edit(Experiment $experiment): JsonResource
    {
        return JsonResource::make($experiment);
    }

    /**
     * @throws Exception
     */
    public function update(ExperimentStoreRequest $request, Experiment $experiment): JsonResource
    {
        $experiment->update($request->validated());

        if ($request->getPreferences()) {
            PreferenceService::createManyForModel($request->getPreferences(), $experiment);
        }

        return JsonResource::make($experiment);
    }

    /**
     * @throws Exception
     */
    public function destroy(Experiment $experiment): JsonResource
    {
        $deleted = $experiment->delete();

        return JsonResource::make([
            'experiment' => $experiment,
            'deleted' => $deleted,
        ]);
    }

    /**
     * @throws Exception
     */
    public function forceDelete(Experiment $experiment): JsonResource
    {
        $experiment->crawlers()->each(function ($crawler) {
            $crawler->proxy()->delete();
            $crawler->agent()->delete();
            $crawler->configuration()->delete();
            $crawler->queues()->delete();
        });
        $experiment->crawlers()->delete();

        $deleted = $experiment->delete();

        return JsonResource::make([
            'experiment' => $experiment,
            'deleted' => $deleted,
        ]);
    }
    public function upload(): JsonResource
    {
        $path = request()->file('file_json')->store('json_files');
        $contents = Storage::get($path);
        $data = json_decode($contents, true);

        try {
            foreach ($data as $item) {
                $requestConfiguration = new ConfigurationStoreRequest($item['configuration'][0]);
                $validationConfiguration = Validator::validate([...$item['configuration'][0], 'user_id' => Auth::id(), 'active' => $item['active']], $requestConfiguration->rules());
                $configuration = Configuration::create($validationConfiguration);
    
                $requestProxy = new ProxyIndexRequest($item['proxy'][0]);
                $proxy = Proxy::create([...$requestProxy->all(), 'user_id' => Auth::id()]);
    
                $requestCrawlers = new StoreCrawlerRequest($item);
                $validationCrawlers = Validator::validate([...$item, 'user_id' => Auth::id()], $requestCrawlers->rules());
                $crawler = Crawler::query()->create([...$validationCrawlers, 'configuration_id' => $configuration->id, 'proxy_id' => $proxy->id]);
    
                $requestQueues = new StoreQueueRequest($item['queues'][0]);
                $validationQueues = Validator::validate([...$item['queues'][0], 'user_id' => Auth::id(), 'crawler_id' => $crawler->id], $requestQueues->rules());
                $queues = Queue::create($validationQueues);
    
                $requestJob = new StoreJobRequest($item['queues'][0]['jobs'][0]);
                $validationJob = Validator::validate([...$item['queues'][0]['jobs'][0], 'user_id' => Auth::id(), 'queue_id' => $queues->id], $requestJob->rules());
                Job::create($validationJob);
    
            }
        } catch (\Throwable $th) {
            throw $th;
        }
        

        return JsonResource::make(Crawler::find($crawler->id));
    }
}