<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ActivityPermissionEnum;
use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(?User $user, Activity $activity): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('manager');
    }

    public function update(User $user, Activity $activity): bool
    {
        return $user->hasRole('admin')
            || $user->id === $activity->manager_id
            || $activity->collaboratorHasPermission($user, ActivityPermissionEnum::UPDATE_ACTIVITY);
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $user->hasRole('admin') || $user->id === $activity->manager_id;
    }

    public function manageCollaborators(User $user, Activity $activity): bool
    {
        return $user->hasRole('admin') || $user->id === $activity->manager_id;
    }

    public function managePayments(User $user, Activity $activity): bool
    {
        return $user->hasRole('admin') || $user->id === $activity->manager_id;
    }

    public function viewAvailabilities(User $user, Activity $activity): bool
    {
        return $user->hasRole('admin')
            || $user->id === $activity->manager_id
            || $activity->collaborators()->where('users.id', $user->id)->exists();
    }

    public function manageAvailabilities(User $user, Activity $activity): bool
    {
        return $user->hasRole('admin')
            || $user->id === $activity->manager_id
            || $activity->collaboratorHasPermission($user, ActivityPermissionEnum::MANAGE_AVAILABILITIES);
    }

    public function viewCycles(User $user, Activity $activity): bool
    {
        return $user->hasRole('admin')
            || $user->id === $activity->manager_id
            || $activity->collaborators()->where('users.id', $user->id)->exists();
    }

    public function manageCycles(User $user, Activity $activity): bool
    {
        return $user->hasRole('admin')
            || $user->id === $activity->manager_id
            || $activity->collaboratorHasPermission($user, ActivityPermissionEnum::MANAGE_CYCLES);
    }
}
