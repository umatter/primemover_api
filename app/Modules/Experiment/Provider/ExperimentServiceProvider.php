<?php

namespace App\Modules\Experiment\Provider;

use App\Modules\Experiment\Service\ExperimentService;
use Illuminate\Support\ServiceProvider;

class ExperimentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ExperimentService::class);
    }
}
