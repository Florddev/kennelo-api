<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Events\NotificationCreated;
use App\Models\Notification as NotificationModel;
use Illuminate\Notifications\Notification;

final class UserDatabaseChannel
{
    public function send(object $notifiable, Notification $notification): ?NotificationModel
    {
        if (! method_exists($notification, 'toUserDatabase')) {
            return null;
        }

        $payload = $notification->toUserDatabase($notifiable);

        $record = NotificationModel::create([
            'user_id' => $notifiable->getKey(),
            'type' => $payload['type'],
            'data' => $payload['data'],
        ]);

        event(new NotificationCreated($record));

        return $record;
    }
}
