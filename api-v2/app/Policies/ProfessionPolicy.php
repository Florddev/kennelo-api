<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Profession;
use App\Models\User;

/**
 * Le référentiel des métiers se lit publiquement et se gère dans le back-office.
 */
class ProfessionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Profession $profession): bool
    {
        return $user->hasRole('admin');
    }
}
