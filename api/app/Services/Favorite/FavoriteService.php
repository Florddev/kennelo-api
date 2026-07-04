<?php

declare(strict_types=1);

namespace App\Services\Favorite;

use App\Enums\NotificationTypeEnum;
use App\Enums\PaginationEnum;
use App\Models\Activity;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class FavoriteService
{
    public function __construct(
        private NotificationService $notifications
    ) {}

    public function list(User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? PaginationEnum::DEFAULT_PAGINATION->value();

        return $user->favoriteActivities()
            ->with(['address', 'media', 'manager.media', 'cycles.settings.animalType', 'cycles.settings.prices'])
            ->withExists(['favoritedBy as is_favorited' => fn (Builder $q) => $q->where('users.id', $user->id)])
            ->whereManagerVerified()
            ->orderByPivot('created_at', 'desc')
            ->paginate($perPage);
    }

    public function add(User $user, Activity $activity): void
    {
        $result = $user->favoriteActivities()->syncWithoutDetaching([$activity->id]);

        if (empty($result['attached'])) {
            return;
        }

        $activity->loadMissing('manager');

        if ($activity->manager !== null) {
            $this->notifications->notify(
                $activity->manager,
                NotificationTypeEnum::FAVORITE_ADDED,
                [
                    'activity_id' => $activity->id,
                    'user_id' => $user->id,
                ],
            );
        }
    }

    public function remove(User $user, Activity $activity): void
    {
        $user->favoriteActivities()->detach($activity->id);
    }
}
