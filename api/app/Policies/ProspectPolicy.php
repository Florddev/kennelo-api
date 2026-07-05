<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Prospect;
use App\Models\User;

class ProspectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Prospect $prospect): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Prospect $prospect): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Prospect $prospect): bool
    {
        return $user->hasRole('admin');
    }

    public function import(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function manageNotes(User $user, Prospect $prospect): bool
    {
        return $user->hasRole('admin');
    }

    public function manageContacts(User $user, Prospect $prospect): bool
    {
        return $user->hasRole('admin');
    }
}
