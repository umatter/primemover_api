<?php

namespace App\Modules\Job\Provider;

use App\Modules\Job\Http\Service\JobService;
use App\Modules\Job\Model\Job;
use Illuminate\Support\ServiceProvider;

class JobServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(JobService::class, function ($app) {
            return new JobService(new Job());
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
