<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\OrganizationPermissionEnum;
use App\Enums\OrganizationRoleEnum;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Détermine qui reçoit une notification.
 */
class NotificationRecipientResolver
{
    /**
     * @return Collection<int, User>
     */
    public function admins(): Collection
    {
        return User::role('admin')->get();
    }

    /**
     * Membres qui ont ce droit dans l'entreprise, ou sur l'activité donnée : le propriétaire, et les membres
     * actifs dont un rôle porte ce droit sur toute l'entreprise ou sur cette activité.
     *
     * @return Collection<int, User>
     */
    public function membersAllowedTo(OrganizationPermissionEnum $permission, Organization $organization, ?string $activityId = null): Collection
    {
        $roles = array_filter(
            OrganizationRoleEnum::cases(),
            fn (OrganizationRoleEnum $role): bool => in_array($permission, $role->permissions(), true),
        );

        return User::query()
            ->whereKey($organization->owner_id)
            ->orWhereHas('organizationMemberships', fn (Builder $query) => $query
                ->active()
                ->whereBelongsTo($organization)
                ->whereHas('roles', fn (Builder $query) => $query
                    ->whereIn('role', $roles)
                    ->where(fn (Builder $query) => $query->whereNull('activity_id')->when(
                        $activityId !== null,
                        fn (Builder $query) => $query->orWhere('activity_id', $activityId),
                    ))))
            ->get();
    }
}
