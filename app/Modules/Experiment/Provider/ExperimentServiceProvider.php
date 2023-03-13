<?php

namespace App\Modules\Experiment\Provider;

use App\Modules\Experiment\Http\Service\ExperimentService;
use App\Modules\Experiment\Model\Experiment;
use Illuminate\Support\ServiceProvider;

class ExperimentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ExperimentService::class, function ($app) {
            return new ExperimentService(new Experiment());
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
