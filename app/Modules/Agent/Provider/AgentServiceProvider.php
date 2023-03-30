<?php

namespace App\Modules\Agent\Provider;

use App\Modules\Agent\Service\AgentService;
use Illuminate\Support\ServiceProvider;

class AgentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AgentService::class);
    }
}
