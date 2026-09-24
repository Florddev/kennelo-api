<?php

declare(strict_types=1);

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrganizationMemberResource;
use App\Models\OrganizationMember;
use App\Services\Organization\TeamService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Invitations reçues par la personne connectée.
 *
 * @tags Organization team
 */
class InvitationController extends Controller
{
    public function __construct(
        private readonly TeamService $team,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return OrganizationMemberResource::collection($this->team->pendingInvitations($request->user()));
    }

    public function accept(OrganizationMember $member): OrganizationMemberResource
    {
        $this->authorize('respond', $member);

        return new OrganizationMemberResource($this->team->accept($member));
    }

    public function decline(OrganizationMember $member): OrganizationMemberResource
    {
        $this->authorize('respond', $member);

        return new OrganizationMemberResource($this->team->decline($member));
    }
}
