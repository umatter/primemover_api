<?php

namespace App\Modules\Experiment\Facade;

use App\Modules\Experiment\Model\DTO\CrawlerDto;
use App\Modules\Experiment\Model\DTO\DownloadData;
use App\Modules\Experiment\Model\Experiment;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void upload(Experiment $experiment, CrawlerDto $crawlerDto)
 * @method static Response download(DownloadData $downloadData)
 *
 * @see \App\Modules\Experiment\Service\ExperimentService
 */
class ExperimentService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Modules\Experiment\Service\ExperimentService::class;
    }
}
