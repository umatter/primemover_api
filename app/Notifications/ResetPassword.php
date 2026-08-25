<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends Notification
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', self::frontendResetUrl($this->token, $notifiable->email))
            ->line('If you did not request a password reset, no further action is required.');
    }

    /**
     * The GUI route that consumes the token (`password/reset/:token?email=`).
     * Built on config, not env(): env() is empty once the config is cached.
     */
    public static function frontendResetUrl(string $token, string $email): string
    {
        return config('app.frontend_url').'/password/reset/'.$token.'?email='.urlencode($email);
    }
}
