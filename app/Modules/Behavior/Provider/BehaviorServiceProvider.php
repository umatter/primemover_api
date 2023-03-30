<?php

namespace App\Modules\Behavior\Provider;

use App\Modules\Behavior\Repository\BehaviorRepository;
use Illuminate\Support\ServiceProvider;

class BehaviorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BehaviorRepository::class);
    }
}
