<?php

namespace App\Modules\Queue\Provider;

use App\Modules\Queue\Repository\QueueRepository;
use Illuminate\Support\ServiceProvider;

class QueueServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(QueueRepository::class);
    }
}
