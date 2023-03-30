<?php

namespace App\Modules\Agent\Provider;

use App\Modules\Agent\Repository\AgentRepository;
use Illuminate\Support\ServiceProvider;

class AgentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AgentRepository::class);
    }
}
