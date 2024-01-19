<?php

namespace App\Http\Middleware;

use Closure;

class HeaderMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function handle($request, Closure $next)
    {
        $token = 'Bearer '.$request->bearerToken();
        $response = $next($request);
        // $response->header('Authorization', $token);
        $response->headers->set('Authorization', $token);

        return $response;
    }
}
