<?php

namespace App\Modules\Job\Provider;

use App\Modules\Job\Repository\JobRepository;
use Illuminate\Support\ServiceProvider;

class JobServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(JobRepository::class);
    }
}
