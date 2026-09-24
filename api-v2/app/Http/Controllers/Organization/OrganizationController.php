<?php

declare(strict_types=1);

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Services\Organization\OrganizationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Organizations
 */
class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organizations,
    ) {}

    /**
     * List my organizations
     *
     * Les entreprises dont la personne connectée est membre active, propriétaire compris.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return OrganizationResource::collection($this->organizations->forMember($request->user()));
    }

    /**
     * Create an organization
     *
     * La personne connectée en devient propriétaire et accède à l'espace de gestion.
     */
    public function store(StoreOrganizationRequest $request): OrganizationResource
    {
        return new OrganizationResource($this->organizations->create($request->user(), $request->validated()));
    }

    public function show(Organization $organization): OrganizationResource
    {
        $this->authorize('view', $organization);

        return new OrganizationResource($organization->load(['address', 'subscription.plan']));
    }

    /**
     * Update an organization
     *
     * Modifier l'identité d'une entreprise vérifiée (raison sociale, forme juridique, SIREN, SIRET)
     * la renvoie en attente de vérification.
     */
    public function update(UpdateOrganizationRequest $request, Organization $organization): OrganizationResource
    {
        $this->authorize('update', $organization);

        return new OrganizationResource($this->organizations->update($organization, $request->validated()));
    }

    /**
     * Close an organization
     *
     * Réservé au propriétaire. Refusé tant qu'un abonnement court ou qu'une réservation est en cours.
     */
    public function destroy(Organization $organization): Response
    {
        $this->authorize('delete', $organization);

        $this->organizations->close($organization);

        return response()->noContent();
    }
}
