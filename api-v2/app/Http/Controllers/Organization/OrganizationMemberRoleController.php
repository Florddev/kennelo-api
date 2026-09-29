<?php

declare(strict_types=1);

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\UpdateMemberRolesRequest;
use App\Http\Resources\OrganizationMemberResource;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Services\Organization\TeamService;

/**
 * @tags Organizations
 */
class OrganizationMemberRoleController extends Controller
{
    /**
     * Replace a member's roles
     *
     * La liste envoyée remplace tous les rôles du membre ; une liste vide les retire tous.
     */
    public function update(UpdateMemberRolesRequest $request, Organization $organization, OrganizationMember $member, TeamService $team): OrganizationMemberResource
    {
        $this->authorize('manageTeam', $organization);

        return new OrganizationMemberResource($team->syncRoles($member, $request->validated('roles')));
    }
}
