<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\NotificationTypeEnum;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotificationService
{
    /**
     * @param  User|Collection<int, User>  $recipients
     * @param  array<string, mixed>  $data
     */
    public function notify(User|Collection $recipients, NotificationTypeEnum $type, array $data = []): void
    {
        if ($type->isTierThree() && ! config('notifications.tier3_enabled')) {
            return;
        }

        $targets = $recipients instanceof User ? collect([$recipients]) : $recipients;

        if ($targets->isEmpty()) {
            return;
        }

        NotificationFacade::send($targets, new AppNotification($type, $data));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return Notification::where('user_id', $user->id)
            ->when(! empty($filters['unread_only']), fn ($q) => $q->whereNull('read_at'))
            ->latest()
            ->paginate($perPage);
    }

    public function unreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function markAsRead(Notification $notification): Notification
    {
        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return $notification->fresh();
    }

    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function delete(Notification $notification): void
    {
        $notification->delete();
    }
}
