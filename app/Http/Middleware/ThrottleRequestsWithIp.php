<?php

namespace App\Http\Middleware;

use App\Facades\Services\ConfigService;
use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ThrottleRequestsWithIp extends ThrottleRequests
{
    /**
     * @param  \Illuminate\Http\Request                   $request
     * @param  Closure                                    $next
     * @param  int                                        $maxAttempts
     * @param  int                                        $decayMinutes
     * @param  string                                     $prefix
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = ''): Response
    {
        Log::debug('1649254282: Throttling request with IP', [
            'ip' => $request->ip(),
            'maxAttempts' => $maxAttempts,
            'decayMinutes' => $decayMinutes,
            'prefix' => $prefix,
        ]);

        $config = ConfigService::getConfigOrFail('primemover.throttle.whitelist');
        $list = explode(',', $config);
        $ipWhitelist = Collection::make($list)->filter(fn (string $ip) => $request->ip() === $ip);

        if ($ipWhitelist->isNotEmpty()) {
            Log::debug('1649254284: IP is whitelisted', [
                'match' => $ipWhitelist->toArray(),
            ]);

            return $next($request);
        }

        return parent::handle($request, $next, $maxAttempts, $decayMinutes, $prefix);

    }
}
