<?php

namespace App\Modules\Param\Provider;

use App\Modules\Param\Http\Service\ParamService;
use App\Modules\Param\Model\Param;
use Illuminate\Support\ServiceProvider;

class ParamServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ParamService::class, function ($app) {
            return new ParamService(new Param());
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
