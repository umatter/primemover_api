<?php

namespace App\Modules\Behavior\Provider;

use App\Modules\Behavior\Service\BehaviorService;
use Illuminate\Support\ServiceProvider;

class BehaviorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BehaviorService::class);
    }
}
