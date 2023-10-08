<?php

namespace App\Modules\Experiment\Pipe\Download;

use App\Modules\Experiment\Model\DTO\DownloadData;
use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PrepareFilePathAndName
{
    public function handle(DownloadData $passable, Closure $next): mixed
    {
        $experiment = $passable->getExperiment();

        $finalArchiveName = '%experiment%_primemover_experiment.zip';
        $pathUploadS3 = 'api-data/experiments/%experiment%_primemover_experiment.zip';
        $crawlerFile = '%experiment%_primemover_crawlers.csv';
        $queueFile = '%experiment%_primemover_queues.csv';
        $jobFile = '%experiment%_primemover_jobs.csv';
        $behaviorFile = '%experiment%_primemover_behaviors.csv';
        $zipFilePath = storage_path('app/%experiment%_primemover_experiment.zip');
        $folderPath = storage_path('app/experiment');

        $passable
            ->setPathUploadS3($this->replacePlaceholder($pathUploadS3, $experiment->id))
            ->setCrawlerFile($this->replacePlaceholder($crawlerFile, $experiment->id))
            ->setQueueFile($this->replacePlaceholder($queueFile, $experiment->id))
            ->setJobFile($this->replacePlaceholder($jobFile, $experiment->id))
            ->setBehaviorFile($this->replacePlaceholder($behaviorFile, $experiment->id))
            ->setZipFilePath($this->replacePlaceholder($zipFilePath, $experiment->id))
            ->setFolderPath($this->replacePlaceholder($folderPath, $experiment->id))
            ->setFinalArchiveName($this->replacePlaceholder($finalArchiveName, $experiment->id));

        Log::info('1682672336: Download processing', [
            'data' => $passable->toArray(),
        ]);

        return $next($passable);
    }

    protected function replacePlaceholder($string, $replacement): string
    {
        return Str::replace('%experiment%', $replacement, $string);
    }
}
