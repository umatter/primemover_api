<?php

namespace App\Modules\Experiment\Factory;

use App\Modules\Behavior\Export\BehaviorExport;
use App\Modules\Crawler\Export\CrawlerExport;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Job\Export\JobExport;
use App\Modules\Queue\Export\QueueExport;
use Illuminate\Support\Facades\App;

class ExportFactory
{
    public function forCrawler(Experiment $experiment): CrawlerExport
    {
        return App::make(CrawlerExport::class, compact('experiment'));
    }

    public function forQueue(Experiment $experiment): QueueExport
    {
        return App::make(QueueExport::class, compact('experiment'));
    }

    public function forBehavior(Experiment $experiment): BehaviorExport
    {
        return App::make(BehaviorExport::class, compact('experiment'));
    }

    public function forJob(Experiment $experiment): JobExport
    {
        return App::make(JobExport::class, compact('experiment'));
    }
}
