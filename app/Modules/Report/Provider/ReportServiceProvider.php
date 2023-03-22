<?php

namespace App\Modules\Report\Provider;

use App\Modules\Report\Http\Service\ReportService;
use App\Modules\Report\Model\Report;
use Illuminate\Support\ServiceProvider;

class ReportServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ReportService::class, function ($app) {
            return new ReportService(new Report());
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
