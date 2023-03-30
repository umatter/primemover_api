<?php

namespace App\Modules\Report\Provider;

use App\Modules\Report\Repository\ReportRepository;
use Illuminate\Support\ServiceProvider;

class ReportServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ReportRepository::class);
    }
}
