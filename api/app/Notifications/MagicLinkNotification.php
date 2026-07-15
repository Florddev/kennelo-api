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

        return (new MailMessage)
            ->subject('Votre lien de connexion à Kennelo')
            ->line('Cliquez sur le bouton ci-dessous pour vous connecter à Kennelo.')
            ->action('Se connecter à Kennelo', $url)
            ->line('Ce lien est valable 15 minutes et ne peut être utilisé qu\'une seule fois.')
            ->line('Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet email.');
    }

    public static function createUrlUsing(\Closure $callback): void
    {
        self::$createUrlUsing = $callback;
    }
}
