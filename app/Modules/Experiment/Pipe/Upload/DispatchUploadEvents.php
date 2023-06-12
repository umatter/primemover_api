<?php

namespace App\Modules\Experiment\Pipe\Upload;

use App\Events\ModelUpdateEvent;
use App\Modules\Experiment\Model\DTO\CrawlerDto;
use Closure;
use Exception;

class DispatchUploadEvents
{
    /**
     * @throws Exception
     */
    public function handle(CrawlerDto $crawlerDto, Closure $next): mixed
    {
        ModelUpdateEvent::dispatch($crawlerDto->getExperiment());

        return $next($crawlerDto);
    }
}
