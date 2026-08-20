<?php

namespace App\Http;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckForMaintenanceMode;
use App\Http\Middleware\CorrelationIdMiddleware;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\FormDataHandler;
use App\Http\Middleware\HeaderMiddleware;
use App\Http\Middleware\OauthRequestModifier;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\ThrottleRequestsWithIp;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Laravel\Passport\Http\Middleware\CheckClientCredentials;

class Kernel extends HttpKernel
{
    /**
     * @var array<string>
     */
    protected $middleware = [
        TrustProxies::class,
        HandleCors::class,
        CheckForMaintenanceMode::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
        SetLocale::class,
        //Middleware\HandleCors::class,
    ];

    /**
     * @var array<string>
     */
    protected $middlewareGroups = [
        'web' => [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
        ],

        'spa' => [
            SubstituteBindings::class,
        ],

        'api' => [
            CorrelationIdMiddleware::class,
            SubstituteBindings::class,
            HeaderMiddleware::class,
            // Runs before a FormRequest's prepareForValidation(), which is what
            // re-supplies user_id from Auth::id(), so stripping a client-sent
            // user_id here cannot break the requests that require the field.
            // Kept AFTER SubstituteBindings so route-model binding is untouched
            // -- see the warning in config/primemover.php. Inert while
            // isolation is off.
            \App\Http\Middleware\StripClientOwnership::class,
        ],
    ];

    /**
     * @var array<string>
     */
    protected $routeMiddleware = [
        'formdata' => FormDataHandler::class,
        'oauth.token' => OauthRequestModifier::class,
        'auth' => Authenticate::class,
        'client' => CheckClientCredentials::class,
        'auth.basic' => AuthenticateWithBasicAuth::class,
        'bindings' => SubstituteBindings::class,
        'cache.headers' => SetCacheHeaders::class,
        'can' => Authorize::class,
        'guest' => RedirectIfAuthenticated::class,
        'password.confirm' => RequirePassword::class,
        'signed' => ValidateSignature::class,
        'throttle' => ThrottleRequests::class,
        'verified' => EnsureEmailIsVerified::class,
        'throttleIp' => ThrottleRequestsWithIp::class,
    ];
}
