<?php

namespace App\Modules\Experiment\Pipe\Upload;

use App\Events\ModelUpdateEvent;
use App\Modules\Experiment\Model\DTO\CrawlerDto;
use Closure;
use Exception;
use Illuminate\Support\Facades\Log;

class DispatchUploadEvents
{
    /**
     * @throws Exception
     */
    public function handle(CrawlerDto $crawlerDto, Closure $next): mixed
    {
        try {
            ModelUpdateEvent::dispatch($crawlerDto->getExperiment());
        } catch (Exception $exception) {
            Log::debug('1682672742: Failed to dispatch model update event', [
                'crawler' => $crawlerDto->getName(),
                'error' => $exception->getMessage(),
            ]);
        }

        return $next($crawlerDto);
    }
}
