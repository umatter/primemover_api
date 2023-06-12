<?php

namespace App\Providers;

use App\Events\ModelUpdateEvent;
use App\Listeners\ModelUpdateListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    protected $listen = [
        ModelUpdateEvent::class => [
            ModelUpdateListener::class,
        ],
    ];
}
