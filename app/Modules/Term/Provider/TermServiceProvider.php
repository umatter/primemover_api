<?php

namespace App\Modules\Term\Provider;

use App\Modules\Term\Service\TermService;
use Illuminate\Support\ServiceProvider;

class TermServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TermService::class);
    }
}
