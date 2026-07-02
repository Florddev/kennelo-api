<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationTypeEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        private NotificationTypeEnum $type,
        private array $data = []
    ) {
        $this->afterCommit();
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return array_merge(['user_database'], $this->type->sendsEmail() ? ['mail'] : []);
    }

    /** @return array{type: string, data: array<string, mixed>} */
    public function toUserDatabase(object $notifiable): array
    {
        return [
            'type' => $this->type->value,
            'data' => $this->data,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $key = 'booking-emails.'.$this->type->value;
        $params = [
            'activity' => (string) ($this->data['activity_name'] ?? ''),
            'amount' => (string) ($this->data['amount'] ?? ''),
        ];

        $mail = (new MailMessage)
            ->subject(__($key.'.subject'))
            ->greeting(__('booking-emails.greeting'))
            ->line(__($key.'.line', $params));

        $bookingId = $this->data['booking_id'] ?? null;

        if ($bookingId !== null) {
            $url = rtrim((string) config('app.frontend_url'), '/').'/bookings/'.$bookingId;
            $mail->action(__('booking-emails.action'), $url);
        }

        return $mail;
    }
}
