<?php

namespace App\Modules\Experiment\Repository;

use App\Facades\Services\PreferenceService;
use App\Modules\Behavior\Export\BehaviorExport;
use App\Modules\Configuration\Http\Request\ConfigurationStoreRequest;
use App\Modules\Configuration\Model\Configuration;
use App\Modules\Crawler\Export\CrawlerExport;
use App\Modules\Crawler\Http\Request\StoreCrawlerRequest;
use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Job\Export\JobExport;
use App\Modules\Job\Http\Request\StoreJobRequest;
use App\Modules\Job\Model\Job;
use App\Modules\Param\Model\Param;
use App\Modules\Proxy\Model\Proxy;
use App\Modules\Queue\Export\QueueExport;
use App\Modules\Queue\Http\Request\StoreQueueRequest;
use App\Modules\Queue\Model\Queue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;
use ZipArchive;

class ExperimentRepository
{
    public function __construct(protected Experiment $experiment)
    {
    }

    public function getData(): LengthAwarePaginator
    {
        return QueryBuilder::for($this->experiment)
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
            ])
            ->latest()
            ->fastPaginate();
    }

    public function all(): Collection
    {
        return $this->experiment->query()
            ->latest()
            ->select(['id', 'name'])
            ->get();
    }

    public function getById($experiment): Experiment
    {
        return $experiment->load([
            'crawlers.agent',
            'crawlers.proxy',
            'crawlers.configuration',
            'crawlers.configuration.preferences',
            'preferences',
        ]);
    }

    public function save($data): Experiment
    {
        $experiment = $this->experiment->query()->create($data->validated());

        if ($data->getPreferences()) {
            PreferenceService::createManyForModel($data->getPreferences(), $experiment);
        }

        return $experiment;
    }

    public function update($data, Experiment $experiment): Experiment
    {
        $experiment->update($data->validated());

        if ($data->getPreferences()) {
            PreferenceService::createManyForModel($data->getPreferences(), $experiment);
        }

        return $experiment;
    }

    public function delete(Experiment $experiment): array
    {
        $deleted = $experiment->delete();

        return [
            'experiment' => $experiment,
            'deleted' => $deleted,
        ];
    }

    public function forceDelete(Experiment $experiment): array
    {
        $experiment->crawlers()->each(function ($crawler): void {
            $crawler->proxy()->delete();
            $crawler->agent()->delete();
            $crawler->configuration()->delete();
            $crawler->queues()->delete();
        });
        $experiment->crawlers()->delete();

        $deleted = $experiment->delete();

        return [
            'experiment' => $experiment,
            'deleted' => $deleted,
        ];
    }

    public function upload(Request $request): Crawler
    {
        $path = $request->file('file_json')->store('json_files');
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

                if ($validationConfiguration->fails()) {
                    JsonResource::wrap('messages');
                    $err = 'error in configuration';

                    return JsonResource::make([
                        $err,
                        'errors' => $validationConfiguration->errors(),
                    ]);
                }

                $configuration = Configuration::create($validationConfiguration->validated());

                // Params
                if ($configuration) {
                    foreach ($requestConfiguration->params as $params) {
                        Param::create([
                            'pi' => $params['pi'],
                            'psi' => $params['psi'],
                            'alpha' => $params['alpha'],
                            'tau' => $params['tau'],
                            'kappa' => $params['kappa'],
                            'beta' => $params['beta'],
                            'search_terms' => $params['search_terms'],
                            'user_id' => Auth::id(),
                            'configuration_id' => $configuration->id,
                        ]);
                    }
                }

                // Proxy
                foreach ($item['proxy'] as $proxy) {
                    $proxy['type'] = $proxy['job_type'];
                    $proxy['user_id'] = Auth::id();
                    unset($proxy['job_type']);
                    $proxy = Proxy::create($proxy);
                }

                // Crawlers
                $requestCrawlers = new StoreCrawlerRequest($item);
                $item['user_id'] = Auth::id();
                $validationCrawlers = Validator::make($item, $requestCrawlers->rules());

                if ($validationCrawlers->fails()) {
                    JsonResource::wrap('messages');
                    $err = 'error in crawlers';

                    return JsonResource::make([
                        $err,
                        'errors' => $validationCrawlers->errors(),
                    ]);
                }

                $crawler = Crawler::query()->create([...$validationCrawlers->validated(), 'configuration_id' => $configuration->id, 'proxy_id' => $proxy->id]);

                // Queues
                foreach ($item['queues'] as $itemQueue) {
                    $itemQueue['user_id'] = Auth::id();
                    $itemQueue['crawler_id'] = $crawler->id;
                    $requestQueues = new StoreQueueRequest($itemQueue);
                    $validationQueues = Validator::make($itemQueue, $requestQueues->rules());

                    if ($validationQueues->fails()) {
                        JsonResource::wrap('messages');
                        $err = 'error in queues';

                        return JsonResource::make([
                            $err,
                            'errors' => $validationQueues->errors(),
                        ]);
                    }

                    $queues = Queue::create($validationQueues->validated());

                    // jobs
                    foreach ($itemQueue['jobs'] as $itemJob) {
                        $itemJob['user_id'] = Auth::id();
                        $itemJob['queue_id'] = $queues->id;

                        $requestJob = new StoreJobRequest($itemJob);
                        $validationJob = Validator::make($itemJob, $requestJob->rules());

                        if ($validationJob->fails()) {
                            JsonResource::wrap('messages');
                            $err = 'error in jobs';

                            return JsonResource::make([
                                $err,
                                'errors' => $validationJob->errors(),
                            ]);
                        }

                        Job::create($validationJob->validated());
                    }
                }
            }
        } catch (Throwable $th) {
            throw $th;
        }

        return $crawler;
    }

    public function download(Experiment $experiment)
    {
        $crawlerFile = "$experiment->id" . "_primemover_crawlers.csv";
        $queueFile = "$experiment->id" . "_primemover_queues.csv";
        $jobFile = "$experiment->id" . "_primemover_jobs.csv";
        $behaviorFile = "$experiment->id" . "_primemover_behaviors.csv";
        $zipFile = storage_path("app/$experiment->id" . "_primemover_experiment.zip");
        // $zipFile = Storage::disk("s3")->put("/api-data/experiments/$experiment->id" . "_primemover_experiment.zip");
        $folderPath = storage_path("app/experiment");

        (new CrawlerExport($experiment))->store('experiment/' . $crawlerFile, 'local');
        (new QueueExport($experiment))->store('experiment/' . $queueFile, 'local');
        (new JobExport($experiment))->store('experiment/' . $jobFile, 'local');
        (new BehaviorExport($experiment))->store('experiment/' . $behaviorFile, 'local');

        $zip = new ZipArchive;
        
        if($zip->open($zipFile, ZipArchive::CREATE) === TRUE)  {
            $this->addFolderToZip($folderPath, $zip);
            $zip->close();

            Storage::disk("local")->delete("experiment/$crawlerFile");
            Storage::disk("local")->delete("experiment/$queueFile");
            Storage::disk("local")->delete("experiment/$jobFile");
            Storage::disk("local")->delete("experiment/$behaviorFile");
            rmdir($folderPath);

            $file = Storage::path("$experiment->id" . "_primemover_experiment.zip");
            $file = str_replace("\0", "", $file);

            Storage::disk("s3")->put("$experiment->id" . "_primemover_experiment.zip", file_get_contents($file));

            return $zipFile;
        }else {
            return 'Failed to create zip file';
        }
    }

    private function addFolderToZip($folderPath, $zip)
    {
        $folderPath = rtrim($folderPath, '/') . '/';
        $files = glob($folderPath . '*');

        foreach ($files as $file) {
            if (is_file($file)) {
                $zip->addFile($file, basename($file));
            }
        }
    }
}
