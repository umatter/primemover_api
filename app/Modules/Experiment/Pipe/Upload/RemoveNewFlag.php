<?php

namespace App\Modules\Experiment\Pipe\Upload;

use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\DTO\CrawlerDto;
use App\Modules\Queue\Model\Queue;
use Closure;
use Exception;

class RemoveNewFlag
{
    /**
     * @throws Exception
     */
    public function handle(CrawlerDto $crawlerDto, Closure $next): mixed
    {
        Crawler::query()->update(['new' => false]);
        Queue::query()->update(['new' => false]);

        return $next($crawlerDto);
    }
}
