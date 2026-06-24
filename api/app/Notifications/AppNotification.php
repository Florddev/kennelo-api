<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationTypeEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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
        return ['user_database'];
    }

    /** @return array{type: string, data: array<string, mixed>} */
    public function toUserDatabase(object $notifiable): array
    {
        return [
            'type' => $this->type->value,
            'data' => $this->data,
        ];
    }
}
