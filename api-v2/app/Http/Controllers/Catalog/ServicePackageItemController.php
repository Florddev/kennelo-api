<?php

declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\ReplacePackageItemsRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Organization;
use App\Models\Service;
use App\Services\Catalog\CatalogService;

/**
 * @tags Services
 */
class ServicePackageItemController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog,
    ) {}

    /**
     * Replace the content of a package
     *
     * La composition complète est remplacée d'un bloc : des prestations simples de la même entreprise.
     */
    public function update(ReplacePackageItemsRequest $request, Organization $organization, Service $service): ServiceResource
    {
        $this->authorize('manageCatalog', $organization);

        return new ServiceResource($this->catalog->replacePackageItems($service, $request->validated('service_ids')));
    }
}
