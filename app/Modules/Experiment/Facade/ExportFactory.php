<?php

namespace App\Modules\Experiment\Facade;

use App\Modules\Behavior\Export\BehaviorExport;
use App\Modules\Crawler\Export\CrawlerExport;
use App\Modules\Experiment\Model\Experiment;
use App\Modules\Job\Export\JobExport;
use App\Modules\Queue\Export\QueueExport;
use Illuminate\Support\Facades\Facade;

/**
 * @method static CrawlerExport  forCrawler(Experiment $experiment)
 * @method static QueueExport    forQueue(Experiment $experiment)
 * @method static JobExport      forJob(Experiment $experiment)
 * @method static BehaviorExport forBehavior(Experiment $experiment)
 *
 * @see \App\Modules\Experiment\Factory\ExportFactory
 */
class ExportFactory extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Modules\Experiment\Factory\ExportFactory::class;
    }
}
