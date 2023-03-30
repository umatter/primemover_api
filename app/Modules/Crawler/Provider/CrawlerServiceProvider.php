<?php

namespace App\Modules\Crawler\Provider;

use App\Modules\Crawler\Model\Crawler;
use App\Modules\Crawler\Service\CrawlerService;
use Illuminate\Support\ServiceProvider;

class CrawlerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(CrawlerService::class, fn ($app) => new CrawlerService(new Crawler()));
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
    }
}
