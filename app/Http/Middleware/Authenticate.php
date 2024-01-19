<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class Authenticate extends Middleware
{
    /**
     * @param Request $request
     */
    protected function redirectTo($request): RedirectResponse
    {
        if ( ! $request->expectsJson()) {
            abort(404);
        }

        return Redirect::back();
    }
}
