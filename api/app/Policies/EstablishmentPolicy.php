<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\EstablishmentPermissionEnum;
use App\Models\Establishment;
use App\Models\User;

class EstablishmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Establishment $establishment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('manager');
    }

    public function update(User $user, Establishment $establishment): bool
    {
        return $user->hasRole('admin')
            || $user->id === $establishment->manager_id
            || $establishment->collaboratorHasPermission($user, EstablishmentPermissionEnum::UPDATE_ESTABLISHMENT);
    }

    public function delete(User $user, Establishment $establishment): bool
    {
        return $user->hasRole('admin') || $user->id === $establishment->manager_id;
    }

    public function managePayments(User $user, Establishment $establishment): bool
    {
        return $user->hasRole('admin') || $user->id === $establishment->manager_id;
    }

    public function viewAvailabilities(User $user, Establishment $establishment): bool
    {
        return $user->hasRole('admin')
            || $user->id === $establishment->manager_id
            || $establishment->collaborators()->where('users.id', $user->id)->exists();
    }

    public function manageAvailabilities(User $user, Establishment $establishment): bool
    {
        return $user->hasRole('admin')
            || $user->id === $establishment->manager_id
            || $establishment->collaboratorHasPermission($user, EstablishmentPermissionEnum::MANAGE_AVAILABILITIES);
    }

    public function viewCapacities(User $user, Establishment $establishment): bool
    {
        return $user->hasRole('admin')
            || $user->id === $establishment->manager_id
            || $establishment->collaborators()->where('users.id', $user->id)->exists();
    }

    public function manageCapacities(User $user, Establishment $establishment): bool
    {
        return $user->hasRole('admin')
            || $user->id === $establishment->manager_id
            || $establishment->collaboratorHasPermission($user, EstablishmentPermissionEnum::MANAGE_CAPACITIES);
    }
}
