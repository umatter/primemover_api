<?php

namespace App\Modules\Outlet\Provider;

use App\Modules\Outlet\Http\Service\OutletService;
use App\Modules\Outlet\Model\Outlet;
use Illuminate\Support\ServiceProvider;

class OutletServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(OutletService::class, function ($app) {
            return new OutletService(new Outlet());
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
