<?php

namespace App\Modules\Experiment\Repository;

use App\Facades\Factories\PipelineFactory;
use App\Facades\Services\DataMapper;
use App\Facades\Services\PreferenceService;
use App\Modules\Experiment\Http\Request\ExperimentUploadRequest;
use App\Modules\Experiment\Model\DTO\CrawlerDto;
use App\Modules\Experiment\Model\DTO\DownloadData;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Experiment\Pipe\Download\AddFilesToZipArchive;
use App\Modules\Experiment\Pipe\Download\CleanUpTempFilesAndFolders;
use App\Modules\Experiment\Pipe\Download\CreateAndStoreExportLocally;
use App\Modules\Experiment\Pipe\Download\CreateZipArchiveOrFail;
use App\Modules\Experiment\Pipe\Download\PrepareFilePathAndName;
use App\Modules\Experiment\Pipe\Download\StoreCreatedZipArchive;
use App\Modules\Experiment\Pipe\Upload\AssertExperimentExists;
use App\Modules\Experiment\Pipe\Upload\CreateConfigurationParams;
use App\Modules\Experiment\Pipe\Upload\CreateConfigurationPreferences;
use App\Modules\Experiment\Pipe\Upload\CreateCrawlerAgent;
use App\Modules\Experiment\Pipe\Upload\CreateCrawlerConfiguration;
use App\Modules\Experiment\Pipe\Upload\CreateCrawlerProxy;
use App\Modules\Experiment\Pipe\Upload\CreateCrawlerQueues;
use App\Modules\Experiment\Pipe\Upload\CreateExperimentCrawlers;
use App\Modules\S3\Facade\S3Service;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Throwable;

class ExperimentRepository
{
    public function __construct(protected Experiment $experiment)
    {
    }

    public function getData(): QueryBuilder
    {
        return QueryBuilder::for($this->experiment)
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::scope('after'),
                AllowedFilter::scope('recent'),
            ])
            ->latest();
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

    public function save($data): Experiment|Model
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

    public function delete(Experiment $experiment): bool
    {
        return $experiment->delete();
    }

    public function forceDelete(Experiment $experiment): bool
    {
        $experiment->crawlers()->each(function ($crawler): void {
            $crawler->proxy()->delete();
            $crawler->agent()->delete();
            $crawler->configuration()->delete();
            $crawler->queues()->delete();
        });
        $experiment->crawlers()->delete();

        return $experiment->delete();
    }

    /**
     * @throws Throwable
     * @throws ValidationException
     */
    public function upload(ExperimentUploadRequest $request, Experiment $experiment): mixed
    {
        return DataMapper::disableEnforceType()
            ->removeUndefinedAttributes()
            ->mapList($request->getJsonFileContent(), CrawlerDto::class)
            ->map(function (CrawlerDto $crawlerDto) use ($experiment) {
                return PipelineFactory::create()
                    ->send($crawlerDto->setExperiment($experiment))
                    ->through([
                        AssertExperimentExists::class,
                        CreateCrawlerConfiguration::class,
                        CreateConfigurationParams::class,
                        CreateConfigurationPreferences::class,
                        CreateCrawlerProxy::class,
                        CreateCrawlerAgent::class,
                        CreateExperimentCrawlers::class,
                        CreateCrawlerQueues::class,
                    ])
                    ->thenReturn();
            });
    }

    public function download(Experiment $experiment): Response
    {
        $passable = DownloadData::from(compact('experiment'));

        return PipelineFactory::create()
            ->send($passable)
            ->through([
                PrepareFilePathAndName::class,
                CreateAndStoreExportLocally::class,
                CreateZipArchiveOrFail::class,
                AddFilesToZipArchive::class,
                CleanUpTempFilesAndFolders::class,
                StoreCreatedZipArchive::class,
            ])
            ->then(fn (DownloadData $passable) => S3Service::downloadFile($passable->getPathUploadS3()));
    }
}
