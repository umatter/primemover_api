<?php

namespace App\Modules\Configuration\Provider;

use App\Modules\Configuration\Model\Configuration;
use App\Modules\Configuration\Service\ConfigurationService;
use Illuminate\Support\ServiceProvider;

class ConfigurationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(ConfigurationService::class, fn ($app) => new ConfigurationService(new Configuration()));
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
