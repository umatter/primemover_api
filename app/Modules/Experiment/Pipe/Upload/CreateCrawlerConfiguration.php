<?php

namespace App\Modules\Experiment\Pipe\Upload;

use App\Modules\Configuration\Model\Configuration;
use App\Modules\Experiment\Model\DTO\CrawlerDto;
use Closure;
use Exception;
use Illuminate\Support\Facades\Auth;
use Throwable;

class CreateCrawlerConfiguration
{
    /**
     * @throws Exception
     */
    public function handle(CrawlerDto $crawlerDto, Closure $next): mixed
    {
        try {
            $itemConfiguration = $crawlerDto
                ->setUserId(Auth::id())
                ->getConfiguration();

            $configuration = Configuration::query()->create([
                'name' => $itemConfiguration->getName(),
                'description' => $itemConfiguration->getDescription(),
                'user_id' => $crawlerDto->getUserId(),
            ]);

            $crawlerDto->setCrawlerConfiguration($configuration);

        } catch (Throwable $e) {
            throw new Exception('Configuration could not be created', 422, $e);
        }

        return $next($crawlerDto);
    }
}
