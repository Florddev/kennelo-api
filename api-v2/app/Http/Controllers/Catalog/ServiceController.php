<?php

declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreServiceRequest;
use App\Http\Requests\Catalog\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Organization;
use App\Models\Service;
use App\Services\Catalog\CatalogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Catalog
 */
class ServiceController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog,
    ) {}

    /**
     * List the catalog
     *
     * Prestations et forfaits de l'entreprise, avec leur grille de prix de base. Réservé à l'équipe.
     */
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('view', $organization);

        return ServiceResource::collection($this->catalog->services($organization));
    }

    /**
     * Create a service
     *
     * La prestation ne se vend qu'une fois ajoutée à l'offre d'une activité.
     */
    public function store(StoreServiceRequest $request, Organization $organization): ServiceResource
    {
        $this->authorize('manageCatalog', $organization);

        return new ServiceResource($this->catalog->create($organization, $request->validated()));
    }

    public function update(UpdateServiceRequest $request, Organization $organization, Service $service): ServiceResource
    {
        $this->authorize('manageCatalog', $organization);

        return new ServiceResource($this->catalog->update($service, $request->validated()));
    }

    /**
     * Delete a service
     *
     * Suppression logique : les réservations passées la gardent. Refusé tant qu'un forfait la contient.
     */
    public function destroy(Organization $organization, Service $service): Response
    {
        $this->authorize('manageCatalog', $organization);

        $this->catalog->delete($service);

        return response()->noContent();
    }
}
