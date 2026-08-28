<?php

namespace App\Modules\Experiment\Service;

use App\Facades\Factories\PipelineFactory;
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
use App\Modules\Experiment\Pipe\Upload\DispatchUploadEvents;
use App\Modules\Experiment\Pipe\Upload\RemoveNewFlag;
use App\Modules\S3\Facade\S3Service;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExperimentService
{
    public function upload(Experiment $experiment, CrawlerDto $crawlerDto): mixed
    {
        return PipelineFactory::create()
            ->withTransaction()
            ->send($crawlerDto->setExperiment($experiment))
            ->through([
                AssertExperimentExists::class,
                RemoveNewFlag::class,
                CreateCrawlerConfiguration::class,
                CreateConfigurationParams::class,
                CreateConfigurationPreferences::class,
                CreateCrawlerProxy::class,
                CreateCrawlerAgent::class,
                CreateExperimentCrawlers::class,
                CreateCrawlerQueues::class,
                // DispatchUploadEvents::class,
            ])
            ->onFailure(function (CrawlerDto $crawlerDto, Throwable $throwable): void {
                Log::error('1682672574: Crawler upload failed', [
                    'crawler' => $crawlerDto->getName(),
                    'error' => $throwable->getMessage(),
                ]);
                throw $throwable;
            })
            ->then(function (CrawlerDto $crawlerDto) {
                Log::info('1682672742: Crawler upload done');

                return $crawlerDto;
            });
    }

    public function download(DownloadData $downloadData): Response
    {
        return PipelineFactory::create()
            ->withTransaction()
            ->withEvents()
            ->send($downloadData)
            ->through([
                PrepareFilePathAndName::class,
                CreateAndStoreExportLocally::class,
                CreateZipArchiveOrFail::class,
                AddFilesToZipArchive::class,
                StoreCreatedZipArchive::class,
                CleanUpTempFilesAndFolders::class,
            ])
            ->onFailure(function (DownloadData $downloadData, Throwable $throwable): Response {
                Log::error('1682672685: Experiment download failed', [
                    'experiment' => $downloadData->getExperiment()->name,
                    's3path' => $downloadData->getPathUploadS3(),
                    'error' => $throwable->getMessage(),
                ]);

                throw $throwable;
            })
            ->then(function (DownloadData $passable) {
                Log::info('1682672710: Experiment download pipeline done', [
                    'id' => $passable->getExperiment()->id,
                    'name' => $passable->getExperiment()->name,
                    's3path' => $passable->getPathUploadS3(),
                ]);

                return S3Service::downloadFile($passable->getPathUploadS3());
            });
    }
}
