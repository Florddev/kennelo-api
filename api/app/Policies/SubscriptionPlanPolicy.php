<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SubscriptionPlan;
use App\Models\User;

class SubscriptionPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, SubscriptionPlan $plan): bool
    {
        return $user->hasRole('admin');
    }
}
