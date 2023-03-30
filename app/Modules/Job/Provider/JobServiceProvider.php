<?php

namespace App\Modules\Job\Provider;

use App\Modules\Job\Service\JobService;
use Illuminate\Support\ServiceProvider;

class JobServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(JobService::class);
    }
}
