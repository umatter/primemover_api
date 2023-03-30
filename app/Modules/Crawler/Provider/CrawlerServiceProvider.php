<?php

namespace App\Modules\Crawler\Provider;

use App\Modules\Crawler\Model\Crawler;
use App\Modules\Crawler\Repository\CrawlerRepository;
use Illuminate\Support\ServiceProvider;

class CrawlerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CrawlerRepository::class, fn ($app) => new CrawlerRepository(new Crawler()));
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
    }
}
