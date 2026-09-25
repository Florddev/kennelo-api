<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OrganizationPermissionEnum;
use App\Models\Activity;
use App\Models\AgendaResource;
use App\Models\Organization;
use App\Models\PricingPeriod;
use App\Models\Service;
use App\Models\User;
use App\Services\Organization\OrganizationPermissions;
use Illuminate\Auth\Access\Response;

/**
 * Comme pour l'entreprise : une personne extérieure reçoit une 404, un membre sans le droit une 403.
 * Gérer une activité demande le droit activity.manage sur elle ; la créer ou la supprimer, sur toute l'entreprise.
 */
class ActivityPolicy
{
    public function __construct(private readonly OrganizationPermissions $permissions) {}

    /**
     * Une activité réservable est publique. Les autres ne sont visibles que de l'équipe et des admins.
     */
    public function view(?User $user, Activity $activity): Response
    {
        if (Activity::query()->whereKey($activity->id)->bookable()->exists()) {
            return Response::allow();
        }

        $isTeamMember = $user !== null && $activity->organization !== null && $this->permissions->isMember($user, $activity->organization);

        return $isTeamMember || $user?->hasRole('admin')
            ? Response::allow()
            : Response::denyAsNotFound(__('errors.not_found'));
    }

    public function create(User $user, Organization $organization): Response
    {
        return $this->can($user, $organization, null);
    }

    public function update(User $user, Activity $activity): Response
    {
        return $this->can($user, $activity->organization, $activity->id);
    }

    public function delete(User $user, Activity $activity): Response
    {
        return $this->can($user, $activity->organization, null);
    }

    /**
     * Une activité ne vend que les prestations du catalogue de son entreprise.
     */
    public function offer(User $user, Activity $activity, Service $service): Response
    {
        if ($service->organization_id !== $activity->organization_id) {
            return Response::denyAsNotFound(__('errors.not_found'));
        }

        return $this->update($user, $activity);
    }

    /**
     * Une activité n'applique que les périodes tarifaires de son entreprise.
     */
    public function price(User $user, Activity $activity, PricingPeriod $period): Response
    {
        if ($period->organization_id !== $activity->organization_id) {
            return Response::denyAsNotFound(__('errors.not_found'));
        }

        return $this->update($user, $activity);
    }

    /**
     * Une activité ne planifie que les ressources de son entreprise.
     */
    public function schedule(User $user, Activity $activity, AgendaResource $resource): Response
    {
        if ($resource->organization_id !== $activity->organization_id) {
            return Response::denyAsNotFound(__('errors.not_found'));
        }

        return $this->update($user, $activity);
    }

    /**
     * Voir les réservations de l'activité, et son agenda : bookings.view sur elle.
     */
    public function viewBookings(User $user, Activity $activity): Response
    {
        $organization = $activity->organization;

        if ($organization === null || ! $this->permissions->isMember($user, $organization)) {
            return Response::denyAsNotFound(__('errors.not_found'));
        }

        return $this->permissions->allows($user, OrganizationPermissionEnum::BOOKINGS_VIEW, $organization, $activity->id)
            ? Response::allow()
            : Response::deny();
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function review(User $user, Activity $activity): bool
    {
        return $user->hasRole('admin');
    }

    private function can(User $user, ?Organization $organization, ?string $activityId): Response
    {
        // Une activité d'une entreprise fermée n'existe plus pour l'équipe.
        if ($organization === null || ! $this->permissions->isMember($user, $organization)) {
            return Response::denyAsNotFound(__('errors.not_found'));
        }

        return $this->permissions->allows($user, OrganizationPermissionEnum::ACTIVITY_MANAGE, $organization, $activityId)
            ? Response::allow()
            : Response::deny();
    }
}
