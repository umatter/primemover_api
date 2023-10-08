<?php

namespace App\Listeners;

use App\Events\ModelUpdateEvent;
use App\Notifications\ModelUpdateNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ModelUpdateListener
{
    public function handle(ModelUpdateEvent $event): void
    {
        Log::info('1686589691: Model update event', [
            'event' => $event->getModel(),
        ]);

        Notification::route('mail', env('NOTIFICATION_TO_EMAIL'))
            ->notify(new ModelUpdateNotification(Auth::user(), $event));

    }
}
