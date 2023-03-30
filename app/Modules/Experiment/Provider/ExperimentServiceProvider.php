<?php

namespace App\Modules\Experiment\Provider;

use App\Modules\Experiment\Repository\ExperimentRepository;
use Illuminate\Support\ServiceProvider;

class ExperimentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ExperimentRepository::class);
    }
}
