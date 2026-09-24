<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

final class MagicLinkNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public static ?\Closure $createUrlUsing = null;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = self::$createUrlUsing !== null
            ? call_user_func(self::$createUrlUsing, $notifiable)
            : URL::temporarySignedRoute(
                'magic-link.verify',
                now()->addMinutes((int) config('auth.magic_link.expire')),
                ['id' => $notifiable->getKey()],
            );

        // Envoyé dans la langue de l'utilisateur (User::preferredLocale()).
        return (new MailMessage)
            ->subject(__('mail.magic_link.subject'))
            ->line(__('mail.magic_link.intro'))
            ->action(__('mail.magic_link.action'), $url)
            ->line(__('mail.magic_link.validity', ['minutes' => (int) config('auth.magic_link.expire')]))
            ->line(__('mail.magic_link.ignore'));
    }

    public static function createUrlUsing(\Closure $callback): void
    {
        self::$createUrlUsing = $callback;
    }
}
