<?php

namespace App\Modules\Term\Provider;

use App\Modules\Term\Repository\TermRepository;
use Illuminate\Support\ServiceProvider;

class TermServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TermRepository::class);
    }
}
