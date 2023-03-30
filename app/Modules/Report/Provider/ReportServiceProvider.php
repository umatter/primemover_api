<?php

namespace App\Modules\Report\Provider;

use App\Modules\Report\Service\ReportService;
use Illuminate\Support\ServiceProvider;

class ReportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ReportService::class);
    }
}
