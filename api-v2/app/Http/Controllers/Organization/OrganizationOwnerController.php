<?php

declare(strict_types=1);

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\TransferOwnershipRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Services\Organization\OrganizationService;

/**
 * @tags Organizations
 */
class OrganizationOwnerController extends Controller
{
    /**
     * Transfer ownership
     *
     * Réservé au propriétaire. Le nouveau propriétaire est un membre actif ; l'ancien reste gérant.
     */
    public function update(TransferOwnershipRequest $request, Organization $organization, OrganizationService $organizations): OrganizationResource
    {
        $this->authorize('transferOwnership', $organization);

        $newOwner = OrganizationMember::findOrFail($request->validated('member_id'));

        return new OrganizationResource($organizations->transferOwnership($organization, $newOwner));
    }
}
