<?php

namespace App\Modules\Experiment\Pipe\Download;

use App\Modules\Experiment\Facade\ExportFactory;
use App\Modules\Experiment\Model\DownloadData;
use Closure;

class CreateAndStoreExportLocally
{
    public function handle(DownloadData $passable, Closure $next): mixed
    {
        $experiment = $passable->getExperiment();

        ExportFactory::forCrawler($experiment)
            ->store('experiment/'.$passable->getCrawlerFile(), 'local');

        ExportFactory::forQueue($experiment)
            ->store('experiment/'.$passable->getQueueFile(), 'local');

        ExportFactory::forJob($experiment)
            ->store('experiment/'.$passable->getJobFile(), 'local');

        ExportFactory::forBehavior($experiment)
            ->store('experiment/'.$passable->getBehaviorFile(), 'local');

        return $next($passable);
    }
}
