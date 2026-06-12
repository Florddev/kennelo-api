<?php

declare(strict_types=1);

namespace App\Services\Favorite;

use App\Enums\PaginationEnum;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class FavoriteService
{
    public function list(User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? PaginationEnum::DEFAULT_PAGINATION->value();

        return $user->favoriteActivities()
            ->with(['address', 'manager', 'cycles.settings.animalType'])
            ->withExists(['favoritedBy as is_favorited' => fn (Builder $q) => $q->where('users.id', $user->id)])
            ->orderByPivot('created_at', 'desc')
            ->paginate($perPage);
    }

    public function add(User $user, Activity $activity): void
    {
        $user->favoriteActivities()->syncWithoutDetaching([$activity->id]);
    }

    public function remove(User $user, Activity $activity): void
    {
        $user->favoriteActivities()->detach($activity->id);
    }
}
