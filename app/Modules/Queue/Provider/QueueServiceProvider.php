<?php

namespace App\Modules\Queue\Provider;

use App\Modules\Queue\Http\Service\QueueService;
use App\Modules\Queue\Model\Queue;
use Illuminate\Support\ServiceProvider;

class QueueServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(QueueService::class, function ($app) {
            return new QueueService(new Queue());
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
