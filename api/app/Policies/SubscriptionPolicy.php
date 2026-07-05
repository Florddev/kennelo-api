<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ActivityPermissionEnum;
use App\Models\Activity;
use App\Models\User;

class SubscriptionPolicy
{
    public function manageForActivity(User $user, Activity $activity): bool
    {
        if ((string) $activity->manager_id === (string) $user->id) {
            return true;
        }

        return $activity->collaboratorHasPermission($user, ActivityPermissionEnum::UPDATE_ACTIVITY);
    }
}
