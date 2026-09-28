<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * La modération des avis signalés revient à Kennelo.
 */
class ReviewReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
