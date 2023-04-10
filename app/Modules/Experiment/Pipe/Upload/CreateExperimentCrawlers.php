<?php

namespace App\Modules\Experiment\Pipe\Upload;

use App\Modules\Crawler\Model\Crawler;
use App\Modules\Experiment\Model\DTO\CrawlerDto;
use Closure;
use Exception;
use Illuminate\Support\Facades\Auth;
use Throwable;

class CreateExperimentCrawlers
{
    /**
     * @throws Exception
     */
    public function handle(CrawlerDto $crawlerDto, Closure $next): mixed
    {
        try {

            $crawler = Crawler::query()->create([
                'name' => $crawlerDto->getName(),
                'description' => $crawlerDto->getDescription(),
                'testing' => $crawlerDto->getTesting(),
                'user_id' => Auth::id(),
                'configuration_id' => $crawlerDto->getCrawlerConfiguration()->id,
                'proxy_id' => $crawlerDto->getCrawlerProxy()->id,
                'agent_id' => $crawlerDto->getCrawlerAgent()->id ?? null,
                'experiment_id' => $crawlerDto->getExperiment()->id,

            ]);

        } catch (Throwable $e) {
            throw new Exception('Crawler could not be created', 422, $e);
        }

        return $next($crawlerDto->setExperimentCrawler($crawler));
    }
}
