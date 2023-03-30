<?php

namespace App\Modules\Queue\Provider;

use App\Modules\Queue\Service\QueueService;
use Illuminate\Support\ServiceProvider;

class QueueServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(QueueService::class);
    }
}
