<?php

declare(strict_types=1);

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\InviteMemberRequest;
use App\Http\Resources\OrganizationMemberResource;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Services\Organization\TeamService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Organization team
 */
class OrganizationMemberController extends Controller
{
    public function __construct(
        private readonly TeamService $team,
    ) {}

    /**
     * List the team
     *
     * Membres actifs et invitations en attente.
     */
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('view', $organization);

        return OrganizationMemberResource::collection($this->team->members($organization));
    }

    /**
     * Invite a member
     *
     * La personne doit déjà avoir un compte Kennelo. Compte dans le quota de membres de l'offre.
     */
    public function store(InviteMemberRequest $request, Organization $organization): OrganizationMemberResource
    {
        $this->authorize('manageTeam', $organization);

        return new OrganizationMemberResource(
            $this->team->invite($organization, $request->user(), $request->validated('email'))
        );
    }

    /**
     * Remove a member or leave
     *
     * Retire un membre, annule une invitation, ou permet à un membre de partir. Le propriétaire ne peut pas partir.
     */
    public function destroy(Organization $organization, OrganizationMember $member): Response
    {
        $this->authorize('delete', $member);

        $this->team->remove($member);

        return response()->noContent();
    }
}
