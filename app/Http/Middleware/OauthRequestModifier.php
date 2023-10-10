<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class OauthRequestModifier
{
    public function handle(Request $request, Closure $next): mixed
    {
        $clientId = Config::get('auth.passport.client_id');
        $clientSecret = Config::get('auth.passport.client_secret');

        $request->merge([
            'grant_type' => 'password',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        Log::debug('1696930621: Handling auth request modifier');

        return $next($request);
    }
}
