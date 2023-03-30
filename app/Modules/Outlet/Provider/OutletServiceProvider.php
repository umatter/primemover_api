<?php

namespace App\Modules\Outlet\Provider;

use App\Modules\Outlet\Repository\OutletRepository;
use Illuminate\Support\ServiceProvider;

class OutletServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(OutletRepository::class);
    }
}
