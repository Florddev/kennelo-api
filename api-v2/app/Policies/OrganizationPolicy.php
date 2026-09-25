<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OrganizationPermissionEnum;
use App\Models\Organization;
use App\Models\User;
use App\Services\Organization\OrganizationPermissions;
use Illuminate\Auth\Access\Response;

/**
 * Une personne extérieure à l'entreprise reçoit une 404 : elle n'a pas à savoir que l'entreprise existe.
 * Un membre sans le droit demandé reçoit une 403.
 */
class OrganizationPolicy
{
    public function __construct(private readonly OrganizationPermissions $permissions) {}

    public function view(User $user, Organization $organization): Response
    {
        return $this->permissions->isMember($user, $organization)
            ? Response::allow()
            : Response::denyAsNotFound(__('errors.not_found'));
    }

    public function update(User $user, Organization $organization): Response
    {
        return $this->can($user, $organization, OrganizationPermissionEnum::ORGANIZATION_MANAGE);
    }

    public function delete(User $user, Organization $organization): Response
    {
        return $this->ownerOnly($user, $organization);
    }

    public function transferOwnership(User $user, Organization $organization): Response
    {
        return $this->ownerOnly($user, $organization);
    }

    public function manageTeam(User $user, Organization $organization): Response
    {
        return $this->can($user, $organization, OrganizationPermissionEnum::TEAM_MANAGE);
    }

    public function manageBilling(User $user, Organization $organization): Response
    {
        return $this->can($user, $organization, OrganizationPermissionEnum::BILLING_MANAGE);
    }

    public function manageCatalog(User $user, Organization $organization): Response
    {
        return $this->can($user, $organization, OrganizationPermissionEnum::CATALOG_MANAGE);
    }

    /**
     * Agenda de toute l'entreprise : bookings.view sur toute l'entreprise. Sinon, l'agenda d'une activité
     * (ActivityPolicy::viewBookings).
     */
    public function viewAgenda(User $user, Organization $organization): Response
    {
        return $this->can($user, $organization, OrganizationPermissionEnum::BOOKINGS_VIEW);
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function review(User $user, Organization $organization): bool
    {
        return $user->hasRole('admin');
    }

    private function can(User $user, Organization $organization, OrganizationPermissionEnum $permission): Response
    {
        if (! $this->permissions->isMember($user, $organization)) {
            return Response::denyAsNotFound(__('errors.not_found'));
        }

        return $this->permissions->allows($user, $permission, $organization)
            ? Response::allow()
            : Response::deny();
    }

    private function ownerOnly(User $user, Organization $organization): Response
    {
        if (! $this->permissions->isMember($user, $organization)) {
            return Response::denyAsNotFound(__('errors.not_found'));
        }

        return $organization->isOwnedBy($user) ? Response::allow() : Response::deny();
    }
}
