<?php

namespace App\Modules\Crawler\Provider;

use App\Modules\Crawler\Http\Service\CrawlerService;
use App\Modules\Crawler\Model\Crawler;
use Illuminate\Support\ServiceProvider;

class CrawlerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(CrawlerService::class, function ($app) {
            return new CrawlerService(new Crawler());
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
