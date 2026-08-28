<?php

namespace App\Modules\Configuration\Provider;

use App\Modules\Configuration\Model\Configuration;
use App\Modules\Configuration\Repository\ConfigurationRepository;
use Illuminate\Support\ServiceProvider;

class ConfigurationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ConfigurationRepository::class, fn ($app) => new ConfigurationRepository(new Configuration()));
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}
}
