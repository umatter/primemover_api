<?php

namespace App\Modules\Proxy\Provider;

use App\Modules\Proxy\Service\ProxyService;
use Illuminate\Support\ServiceProvider;

class ProxyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProxyService::class);
    }
}
