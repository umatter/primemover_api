<?php

namespace App\Modules\Experiment\Pipe\Upload;

use App\Modules\Agent\Model\Agent;
use App\Modules\Experiment\Model\DTO\CrawlerDto;
use Closure;
use Exception;
use Illuminate\Support\Facades\Auth;
use Throwable;

class CreateCrawlerAgent
{
    /**
     * @throws Exception
     */
    public function handle(CrawlerDto $crawlerDto, Closure $next): mixed
    {
        if ( ! $agent = $crawlerDto->setUserId(Auth::id())->getAgent()) {
            return $next($crawlerDto);
        }

        try {
            $crawlerAgent = Agent::query()->create([
                'name' => $agent->getName(),
                'description' => $agent->getDescription(),
                'identification' => $agent->getIdentification(),
                'multilogin_id' => $agent->getMultiloginId(),
                'multilogin_profile' => $agent->getMultiloginProfile(),
                'location' => $agent->getLocation(),
                'user_id' => $crawlerDto->getUserId(),
            ]);

        } catch (Throwable $e) {
            throw new Exception('Agent could not be created', 422, $e);
        }

        return $next($crawlerDto->setCrawlerAgent($crawlerAgent));
    }
}
