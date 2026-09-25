<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProfessionCategory;
use App\Models\User;

class ProfessionCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, ProfessionCategory $category): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, ProfessionCategory $category): bool
    {
        return $user->hasRole('admin');
    }
}
