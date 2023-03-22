<?php

namespace App\Modules\Agent\Provider;

use App\Modules\Agent\Http\Service\AgentService;
use App\Modules\Agent\Model\Agent;
use Illuminate\Support\ServiceProvider;

class AgentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(AgentService::class, function ($app) {
            return new AgentService(new Agent());
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
