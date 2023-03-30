<?php

namespace App\Modules\Param\Provider;

use App\Modules\Param\Repository\ParamRepository;
use Illuminate\Support\ServiceProvider;

class ParamServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ParamRepository::class);
    }
}
