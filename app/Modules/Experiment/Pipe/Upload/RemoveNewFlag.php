<?php

namespace App\Modules\Experiment\Pipe\Upload;

use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\DTO\CrawlerDto;
use App\Modules\Queue\Model\Queue;
use Closure;
use Exception;
use Illuminate\Support\Facades\Log;

class RemoveNewFlag
{
    /**
     * @throws Exception
     */
    public function handle(CrawlerDto $crawlerDto, Closure $next): mixed
    {
        Crawler::query()->update(['new' => false]);
        Queue::query()->update(['new' => false]);
        Log::info('1682672094: New flag removed from crawlers and queues');

        return $next($crawlerDto);
    }
}
