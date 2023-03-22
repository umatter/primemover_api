<?php

namespace App\Modules\Configuration\Provider;

use App\Modules\Configuration\Http\Service\ConfigurationService;
use App\Modules\Configuration\Model\Configuration;
use Illuminate\Support\ServiceProvider;

class ConfigurationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ConfigurationService::class, function ($app) {
            return new ConfigurationService(new Configuration());
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
