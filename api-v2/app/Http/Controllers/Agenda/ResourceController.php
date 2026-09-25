<?php

declare(strict_types=1);

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agenda\StoreResourceRequest;
use App\Http\Requests\Agenda\UpdateResourceRequest;
use App\Http\Resources\AgendaResourceResource;
use App\Models\AgendaResource;
use App\Models\Organization;
use App\Services\Agenda\ResourceService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Personnes, équipements et espaces réservables dans l'agenda de l'entreprise.
 *
 * @tags Agenda
 */
class ResourceController extends Controller
{
    public function __construct(
        private readonly ResourceService $resources,
    ) {}

    /**
     * List the resources
     *
     * Avec leurs plannings dans chaque activité. Réservé à l'équipe.
     */
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('view', $organization);

        return AgendaResourceResource::collection($this->resources->forOrganization($organization));
    }

    /**
     * Create a resource
     *
     * Une personne est un membre actif de l'équipe (organization_member_id), qui n'a qu'une fiche.
     */
    public function store(StoreResourceRequest $request, Organization $organization): AgendaResourceResource
    {
        $this->authorize('manageCatalog', $organization);

        return new AgendaResourceResource($this->resources->create($organization, $request->validated()));
    }

    /**
     * Update a resource
     *
     * Désactivée, elle ne reçoit plus de rendez-vous ; ceux déjà pris restent.
     */
    public function update(UpdateResourceRequest $request, Organization $organization, AgendaResource $resource): AgendaResourceResource
    {
        $this->authorize('manageCatalog', $organization);

        return new AgendaResourceResource($this->resources->update($resource, $request->validated()));
    }

    /**
     * Delete a resource
     *
     * Une ressource qui a déjà eu des rendez-vous se désactive plutôt.
     */
    public function destroy(Organization $organization, AgendaResource $resource): Response
    {
        $this->authorize('manageCatalog', $organization);

        $this->resources->delete($resource);

        return response()->noContent();
    }
}
