<?php

declare(strict_types=1);

namespace App\Services\Organization;

use App\Enums\OrganizationMemberStatusEnum;
use App\Enums\OrganizationPermissionEnum;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\OrganizationMemberRole;
use App\Models\User;

/**
 * Répond à la seule question d'autorisation d'une entreprise : ce membre a-t-il ce droit, ici ?
 *
 * Le propriétaire a tous les droits. Un autre membre actif tient les siens de ses rôles : un rôle sans
 * activité vaut pour toute l'entreprise, un rôle lié à une activité ne vaut que pour celle-ci.
 * Les appartenances de l'utilisateur sont chargées une fois, puis relues en mémoire à chaque vérification.
 */
class OrganizationPermissions
{
    public function allows(User $user, OrganizationPermissionEnum $permission, Organization $organization, ?string $activityId = null): bool
    {
        return in_array($permission, $this->permissionsFor($user, $organization, $activityId), true);
    }

    public function isMember(User $user, Organization $organization): bool
    {
        return $organization->isOwnedBy($user) || $this->membership($user, $organization) !== null;
    }

    /**
     * @return list<OrganizationPermissionEnum>
     */
    public function permissionsFor(User $user, Organization $organization, ?string $activityId = null): array
    {
        if ($organization->isOwnedBy($user)) {
            return OrganizationPermissionEnum::cases();
        }

        $membership = $this->membership($user, $organization);

        if ($membership === null) {
            return [];
        }

        return $membership->roles
            ->filter(fn (OrganizationMemberRole $role): bool => $role->activity_id === null || $role->activity_id === $activityId)
            ->flatMap(fn (OrganizationMemberRole $role): array => $role->role->permissions())
            ->unique(fn (OrganizationPermissionEnum $permission): string => $permission->value)
            ->values()
            ->all();
    }

    private function membership(User $user, Organization $organization): ?OrganizationMember
    {
        return $user->loadMissing('organizationMemberships.roles')
            ->organizationMemberships
            ->first(fn (OrganizationMember $member): bool => $member->organization_id === $organization->id
                && $member->status === OrganizationMemberStatusEnum::ACTIVE);
    }
}
