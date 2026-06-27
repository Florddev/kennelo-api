<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TwoFactorStatusNotification extends Notification
{
    use Queueable;

    public function __construct(private bool $enabled) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        if ($this->enabled) {
            return (new MailMessage)
                ->subject('Two-factor authentication enabled')
                ->line('Two-factor authentication has just been enabled on your Kennelo account.')
                ->line('If you did not perform this action, please reset your password and contact support immediately.');
        }

        return (new MailMessage)
            ->subject('Two-factor authentication disabled')
            ->line('Two-factor authentication has just been disabled on your Kennelo account.')
            ->line('If you did not perform this action, please reset your password and contact support immediately.');
    }
}
