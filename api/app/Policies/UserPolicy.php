<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->hasRole('admin');
    }

    public function update(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->hasRole('admin');
    }

    public function destroy(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->hasRole('admin');
    }

    public function updateStatus(User $user, User $model): bool
    {
        return $user->hasRole('admin') && $user->id !== $model->id;
    }

    public function assignRoles(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    public function reviewIdentityVerification(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    public function ban(User $user, User $model): bool
    {
        return $user->hasRole('admin')
            && $user->id !== $model->id
            && ! $model->hasRole('admin');
    }

    public function unban(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    public function forcePasswordReset(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    public function verifyEmail(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }
}
