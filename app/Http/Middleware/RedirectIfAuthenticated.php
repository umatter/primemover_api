<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, ?string $guard = null): mixed
    {
        if (Auth::guard($guard)->check()) {
            return response()->json(['error' => 'Already authenticated.'], 400);
        }

        return $next($request);
    }
}
