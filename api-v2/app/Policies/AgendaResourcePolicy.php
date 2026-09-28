<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\OrganizationPermissionEnum;
use App\Models\AgendaResource;
use App\Models\User;
use App\Services\Organization\OrganizationPermissions;
use Illuminate\Auth\Access\Response;

/**
 * Les ressources se gèrent avec le catalogue (OrganizationPolicy::manageCatalog) et leurs plannings avec l'activité
 * (ActivityPolicy::schedule). Restent leurs absences et blocages.
 */
class AgendaResourcePolicy
{
    public function __construct(private readonly OrganizationPermissions $permissions) {}

    /**
     * Poser ou retirer une absence ou un blocage : agenda.manage sur toute l'entreprise, ou sur une activité où la
     * ressource travaille. Une personne gère aussi les siennes, sans ce droit.
     */
    public function manageAvailability(User $user, AgendaResource $resource): Response
    {
        $organization = $resource->organization;

        if ($organization === null || ! $this->permissions->isMember($user, $organization)) {
            return Response::denyAsNotFound(__('errors.not_found'));
        }

        if ($resource->member !== null && $resource->member->user_id === $user->id) {
            return Response::allow();
        }

        $activityIds = [null, ...$resource->schedules()->reorder()->distinct()->pluck('activity_id')->all()];

        foreach ($activityIds as $activityId) {
            if ($this->permissions->allows($user, OrganizationPermissionEnum::AGENDA_MANAGE, $organization, $activityId)) {
                return Response::allow();
            }
        }

        return Response::deny();
    }
}
