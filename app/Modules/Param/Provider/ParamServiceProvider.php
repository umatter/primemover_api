<?php

namespace App\Modules\Param\Provider;

use App\Modules\Param\Service\ParamService;
use Illuminate\Support\ServiceProvider;

class ParamServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ParamService::class);
    }
}
