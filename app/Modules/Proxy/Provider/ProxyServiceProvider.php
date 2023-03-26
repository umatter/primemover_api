<?php

namespace App\Modules\Proxy\Provider;

use App\Modules\Proxy\Http\Service\ProxyService;
use App\Modules\Proxy\Model\Proxy;
use Illuminate\Support\ServiceProvider;

class ProxyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ProxyService::class, function ($app) {
            return new ProxyService(new Proxy());
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
