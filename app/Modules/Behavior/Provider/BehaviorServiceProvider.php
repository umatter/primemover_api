<?php

namespace App\Modules\Behavior\Provider;

use App\Modules\Behavior\Http\Service\BehaviorService;
use App\Modules\Behavior\Model\Behavior;
use Illuminate\Support\ServiceProvider;

class BehaviorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(BehaviorService::class, function ($app) {
            return new BehaviorService(new Behavior());
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
