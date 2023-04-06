<?php

namespace App\Modules\Experiment\Pipe\Upload;

use App\Modules\Experiment\Model\DTO\CrawlerDto;
use App\Modules\Proxy\Model\Proxy;
use Closure;
use Exception;
use Illuminate\Support\Facades\Auth;
use Throwable;

class CreateCrawlerProxy
{
    /**
     * @throws Exception
     */
    public function handle(CrawlerDto $crawlerDto, Closure $next): mixed
    {
        try {
            $proxy = $crawlerDto
                ->setUserId(Auth::id())
                ->getProxy();

             $crawlerProxy = Proxy::query()->create([
                 'name' => $proxy->getName(),
                 'description' => $proxy->getDescription(),
                 'hostname' => $proxy->getHostname(),
                 'port' => $proxy->getPort(),
                 'username' => $proxy->getUsername(),
                 'password' => $proxy->getPassword(),
                 'type' => $proxy->getType(),
                 'user_id' => $crawlerDto->getUserId(),
             ]);

        } catch (Throwable $e) {
            throw new Exception('Proxy could not be created', 422, $e);
        }

        return $next($crawlerDto->setCrawlerProxy($crawlerProxy));
    }
}
