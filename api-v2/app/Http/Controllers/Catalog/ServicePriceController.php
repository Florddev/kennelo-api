<?php

declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\ReplaceServicePricesRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Organization;
use App\Models\Service;
use App\Services\Catalog\CatalogService;

/**
 * @tags Services
 */
class ServicePriceController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog,
    ) {}

    /**
     * Replace the price grid
     *
     * La grille complète est remplacée d'un bloc. Pour un animal, la ligne la plus précise l'emporte :
     * race, puis taille et poil, puis taille, puis espèce.
     */
    public function update(ReplaceServicePricesRequest $request, Organization $organization, Service $service): ServiceResource
    {
        $this->authorize('manageCatalog', $organization);

        return new ServiceResource($this->catalog->replacePrices($service, $request->validated('prices')));
    }
}
