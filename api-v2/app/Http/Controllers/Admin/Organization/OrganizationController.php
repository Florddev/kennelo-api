<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Organization;

use App\Enums\AdminActionTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Organization\ListOrganizationsRequest;
use App\Http\Requests\Admin\Organization\ReviewOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Services\Admin\AdminActionService;
use App\Services\Admin\Organization\OrganizationReviewService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Admin Organizations
 */
class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationReviewService $reviews,
        private readonly AdminActionService $actions,
    ) {}

    public function index(ListOrganizationsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Organization::class);

        return OrganizationResource::collection($this->reviews->paginate($request->validated()));
    }

    public function show(Organization $organization): OrganizationResource
    {
        $this->authorize('review', $organization);

        return $this->resource($organization);
    }

    public function approve(Request $request, Organization $organization): OrganizationResource
    {
        $this->authorize('review', $organization);

        $this->reviews->approve($organization, $request->user());
        $this->log($request, $organization, AdminActionTypeEnum::APPROVE_ORGANIZATION);

        return $this->resource($organization);
    }

    public function reject(ReviewOrganizationRequest $request, Organization $organization): OrganizationResource
    {
        $this->authorize('review', $organization);

        $this->reviews->reject($organization, $request->user(), $request->validated('reason'));
        $this->log($request, $organization, AdminActionTypeEnum::REJECT_ORGANIZATION, $request->validated());

        return $this->resource($organization);
    }

    public function suspend(ReviewOrganizationRequest $request, Organization $organization): OrganizationResource
    {
        $this->authorize('review', $organization);

        $this->reviews->suspend($organization, $request->user(), $request->validated('reason'));
        $this->log($request, $organization, AdminActionTypeEnum::SUSPEND_ORGANIZATION, $request->validated());

        return $this->resource($organization);
    }

    /**
     * Compare with the public company register
     *
     * Enregistre les données du registre dans verification_data, sans changer le statut.
     */
    public function verifyCompany(Request $request, Organization $organization): OrganizationResource
    {
        $this->authorize('review', $organization);

        $this->reviews->verifyCompany($organization);
        $this->log($request, $organization, AdminActionTypeEnum::VERIFY_ORGANIZATION_COMPANY);

        return $this->resource($organization);
    }

    private function resource(Organization $organization): OrganizationResource
    {
        return new OrganizationResource($organization->load(['owner.media', 'address', 'subscription.plan']));
    }

    /**
     * Le journal admin vise des utilisateurs : l'action est rattachée au propriétaire, l'entreprise est en métadonnée.
     *
     * @param  array<string, mixed>  $metadata
     */
    private function log(Request $request, Organization $organization, AdminActionTypeEnum $action, array $metadata = []): void
    {
        $this->actions->log($request->user(), $organization->owner, $action, ['organization_id' => $organization->id, ...$metadata]);
    }
}
