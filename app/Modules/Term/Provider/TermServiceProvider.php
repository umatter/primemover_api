<?php

namespace App\Modules\Term\Provider;

use App\Modules\Term\Http\Service\TermService;
use App\Modules\Term\Model\Term;
use Illuminate\Support\ServiceProvider;

class TermServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(TermService::class, function ($app) {
            return new TermService(new Term());
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
