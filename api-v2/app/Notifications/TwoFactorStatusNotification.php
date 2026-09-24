<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TwoFactorStatusNotification extends Notification implements ShouldQueue
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
        // Envoyé dans la langue de l'utilisateur (User::preferredLocale()).
        $key = $this->enabled ? 'mail.two_factor_enabled' : 'mail.two_factor_disabled';

        return (new MailMessage)
            ->subject(__($key.'.subject'))
            ->line(__($key.'.line'))
            ->line(__('mail.security_notice'));
    }
}
