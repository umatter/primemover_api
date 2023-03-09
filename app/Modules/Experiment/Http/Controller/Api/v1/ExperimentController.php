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
use App\Modules\Param\Model\Param;
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
                // Configuration
                $itemConfiguration = $item['configuration'][0];
                $itemConfiguration['user_id'] = Auth::id();
                $itemConfiguration['active'] = $item['active'];

                $requestConfiguration = new ConfigurationStoreRequest($itemConfiguration);
                $validationConfiguration = Validator::make($itemConfiguration, $requestConfiguration->rules());

                if($validationConfiguration->fails()) {
                    JsonResource::wrap('messages');
                    $err = "error in configuration";
                    return JsonResource::make([
                        $err,
                        "errors" => $validationConfiguration->errors()
                    ]);
                }

                $configuration = Configuration::create($validationConfiguration->validated());

                // Params
                if($configuration) {
                    foreach($requestConfiguration->params as $params){
                        Param::create([
                            'pi' => $params["pi"],
                            'psi' => $params["psi"],
                            'alpha' => $params["alpha"],
                            'tau' => $params["tau"],
                            'kappa' => $params["kappa"],
                            'beta' => $params["beta"],
                            'search_terms' => $params['search_terms'],
                            'user_id' => Auth::id(),
                            'configuration_id' => $configuration->id
                        ]);
                    }
                }
                
                // Proxy
                foreach($item['proxy'] as $proxy) {
                    $proxy['type'] = $proxy['job_type'];
                    $proxy['user_id'] = Auth::id();
                    unset($proxy['job_type']);
                    $proxy = Proxy::create($proxy);
                }

                // Crawlers
                $requestCrawlers = new StoreCrawlerRequest($item);
                $item['user_id'] = Auth::id();
                $validationCrawlers = Validator::make($item, $requestCrawlers->rules());

                if($validationCrawlers->fails()) {
                    JsonResource::wrap('messages');
                    $err = "error in crawlers";
                    return JsonResource::make([
                        $err,
                        "errors" => $validationCrawlers->errors()
                    ]);
                }
                
                $crawler = Crawler::query()->create([...$validationCrawlers->validated(), 'configuration_id' => $configuration->id, 'proxy_id' => $proxy->id]);
     
                // Queues
                foreach($item['queues'] as $itemQueue){
                    $itemQueue['user_id'] = Auth::id();
                    $itemQueue['crawler_id'] = $crawler->id;
                    $requestQueues = new StoreQueueRequest($itemQueue);
                    $validationQueues = Validator::make($itemQueue, $requestQueues->rules());
    
                    if($validationQueues->fails()) {
                        JsonResource::wrap('messages');
                        $err = "error in queues";
                        return JsonResource::make([
                            $err,
                            "errors" => $validationQueues->errors()
                        ]);
                    }
    
                    $queues = Queue::create($validationQueues->validated());
        
                    // jobs
                    foreach($itemQueue['jobs'] as $itemJob) {
                        $itemJob['user_id'] = Auth::id();
                        $itemJob['queue_id'] = $queues->id;
        
                        $requestJob = new StoreJobRequest($itemJob);
                        $validationJob = Validator::make($itemJob, $requestJob->rules());
        
                        if($validationJob->fails()) {
                            JsonResource::wrap('messages');
                            $err = "error in jobs";
                            return JsonResource::make([
                                $err,
                                "errors" => $validationJob->errors()
                            ]);
                        }
        
                        Job::create($validationJob->validated());
                    }
                }
    
            }
        } catch (\Throwable $th) {
            throw $th;
        }
        

        return JsonResource::make(Crawler::find($crawler->id));
    }
}