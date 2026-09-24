<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\NotificationTypeEnum;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotificationService
{
    private const int UNREAD_COUNT_CACHE_TTL = 30;

    /**
     * @param  User|Collection<int, User>  $recipients
     * @param  array<string, mixed>  $data
     */
    public function notify(User|Collection $recipients, NotificationTypeEnum $type, array $data = []): void
    {
        if ($type->isTierThree() && ! setting('tier3_enabled')) {
            return;
        }

        $targets = $recipients instanceof User ? collect([$recipients]) : $recipients;

        if ($targets->isEmpty()) {
            return;
        }

        NotificationFacade::send($targets, new AppNotification($type, $data));

        $targets->each(fn (User $target) => Cache::forget($this->unreadCountCacheKey((string) $target->id)));
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
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function unreadCount(User $user): int
    {
        return (int) Cache::remember(
            $this->unreadCountCacheKey((string) $user->id),
            self::UNREAD_COUNT_CACHE_TTL,
            fn (): int => Notification::where('user_id', $user->id)
                ->whereNull('read_at')
                ->count()
        );
    }

    public function markAsRead(Notification $notification): Notification
    {
        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);

            Cache::forget($this->unreadCountCacheKey((string) $notification->user_id));
        }

        return $notification;
    }

    public function markAllAsRead(User $user): int
    {
        $updated = Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        Cache::forget($this->unreadCountCacheKey((string) $user->id));

        return $updated;
    }

    private function unreadCountCacheKey(string $userId): string
    {
        return "notifications:unread_count:{$userId}";
    }

    public function delete(Notification $notification): void
    {
        $userId = (string) $notification->user_id;

        $notification->delete();

        Cache::forget($this->unreadCountCacheKey($userId));
    }
}
