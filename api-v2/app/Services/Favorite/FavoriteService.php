<?php

declare(strict_types=1);

namespace App\Services\Favorite;

use App\Enums\NotificationTypeEnum;
use App\Enums\OrganizationPermissionEnum;
use App\Models\Activity;
use App\Models\User;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class FavoriteService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly NotificationRecipientResolver $recipients,
    ) {}

    /**
     * Favoris encore réservables, du plus récent au plus ancien.
     */
    public function list(User $user, ?int $perPage = null): LengthAwarePaginator
    {
        return $user->favoriteActivities()
            ->bookable()
            ->with(['organization', 'profession.category', 'address', 'animalTypes', 'media'])
            ->withExists(['favoritedBy as is_favorited' => fn (Builder $query) => $query->whereKey($user->id)])
            ->orderByPivot('created_at', 'desc')
            ->orderBy('activities.id')
            ->paginate($perPage);
    }

    /**
     * Sans effet si l'activité est déjà en favori, y compris lors de deux ajouts simultanés.
     */
    public function add(User $user, Activity $activity): void
    {
        $added = $user->favoriteActivities()->newPivotStatement()->insertOrIgnore([
            'user_id' => $user->id,
            'activity_id' => $activity->id,
            'created_at' => now(),
        ]);

        if ($added === 0 || $activity->organization === null) {
            return;
        }

        $this->notifications->notify(
            $this->recipients->membersAllowedTo(OrganizationPermissionEnum::ACTIVITY_MANAGE, $activity->organization, $activity->id),
            NotificationTypeEnum::FAVORITE_ADDED,
            ['activity_id' => $activity->id, 'activity_name' => $activity->name],
        );
    }

    public function remove(User $user, Activity $activity): void
    {
        $user->favoriteActivities()->detach($activity->id);
    }
}
