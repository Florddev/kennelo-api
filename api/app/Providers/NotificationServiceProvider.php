<?php

declare(strict_types=1);

namespace App\Providers;

use App\Notifications\Channels\UserDatabaseChannel;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Notification::resolved(function (ChannelManager $service): void {
            $service->extend('user_database', fn ($app): UserDatabaseChannel => $app->make(UserDatabaseChannel::class));
        });
    }
}
