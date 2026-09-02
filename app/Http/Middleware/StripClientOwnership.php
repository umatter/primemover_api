<?php

namespace App\Http\Middleware;

use App\Access\Isolation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Drops any client-supplied user_id from the request body.
 *
 * Several store requests declare 'user_id' => ['required'] and supply it
 * themselves in prepareForValidation() via Auth::id(). That runs AFTER
 * middleware, so removing the field here cannot break them -- the request class
 * puts the correct value back. What it does prevent is an isolated researcher
 * asserting a different owner on create, which under derived ownership would let
 * them file rows under someone else's experiment.
 *
 * A no-op while isolation is off or for service accounts, so the runner and the
 * upload pipeline are unaffected.
 */
class StripClientOwnership
{
    public function handle(Request $request, Closure $next): Response
    {
        if ( ! Isolation::bypasses()) {
            $request->request->remove('user_id');
            $request->query->remove('user_id');
        }

        return $next($request);
    }
}
