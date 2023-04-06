<?php

namespace App\Modules\Experiment\Pipe\Upload;

use App\Modules\Experiment\Model\DTO\CrawlerDto;
use Closure;
use Exception;

class AssertExperimentExists
{
    /**
     * @throws Exception
     */
    public function handle(CrawlerDto $crawlerDto, Closure $next): mixed
    {
        if ($crawlerDto->getExperiment()) {
            return $next($crawlerDto);

        }
        throw new Exception('Experiment not found', 422);
    }
}
