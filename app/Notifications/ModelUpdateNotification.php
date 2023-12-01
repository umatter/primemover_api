<?php

namespace App\Notifications;

use App\Events\ModelUpdateEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class ModelUpdateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Authenticatable $user, protected ModelUpdateEvent $event)
    {
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = $notifiable->routes['mail'];
        $data = $this->event
            ->getModel()
            ->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        Log::info('1686589762: Model update notification', [
            'notifiable' => $notifiable,
        ]);

        return (new MailMessage())
            ->line("Hello {$mail}, {$this->user->name} just updated a model:")
            ->line($data);
    }
}
