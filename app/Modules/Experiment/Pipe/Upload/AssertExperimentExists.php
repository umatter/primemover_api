<?php

namespace App\Modules\Experiment\Pipe\Upload;

use App\Modules\Experiment\Model\DTO\CrawlerDto;
use Closure;
use Exception;
use Illuminate\Support\Facades\Log;

class AssertExperimentExists
{
    /**
     * @throws Exception
     */
    public function handle(CrawlerDto $crawlerDto, Closure $next): mixed
    {
        if ($crawlerDto->getExperiment()) {
            Log::info('1682672149: Experiment found', ['experiment' => $crawlerDto->getExperiment()]);

            return $next($crawlerDto);

        }
        throw new Exception('Experiment not found', 422);
    }
}
