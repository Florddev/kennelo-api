<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pricing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pricing\StorePricingPeriodRequest;
use App\Http\Requests\Pricing\UpdatePricingPeriodRequest;
use App\Http\Resources\PricingPeriodResource;
use App\Models\Organization;
use App\Models\PricingPeriod;
use App\Services\Pricing\PricingPeriodService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Périodes tarifaires de l'entreprise, partagées par ses activités.
 *
 * @tags Pricing
 */
class PricingPeriodController extends Controller
{
    public function __construct(
        private readonly PricingPeriodService $periods,
    ) {}

    /**
     * List the pricing periods
     *
     * La période de base d'abord, puis les autres dans leur ordre de création. Réservé à l'équipe.
     */
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('view', $organization);

        return PricingPeriodResource::collection($this->periods->forOrganization($organization));
    }

    /**
     * Create a pricing period
     *
     * Compte dans le quota de périodes de l'offre, hors période de base.
     */
    public function store(StorePricingPeriodRequest $request, Organization $organization): PricingPeriodResource
    {
        $this->authorize('manageCatalog', $organization);

        return new PricingPeriodResource($this->periods->create($organization, $request->validated()));
    }

    /**
     * Update a pricing period
     *
     * La période de base ne change que de nom et de couleur.
     */
    public function update(UpdatePricingPeriodRequest $request, Organization $organization, PricingPeriod $pricingPeriod): PricingPeriodResource
    {
        $this->authorize('manageCatalog', $organization);

        return new PricingPeriodResource($this->periods->update($pricingPeriod, $request->validated()));
    }

    /**
     * Delete a pricing period
     *
     * La période de base ne se supprime pas.
     */
    public function destroy(Organization $organization, PricingPeriod $pricingPeriod): Response
    {
        $this->authorize('manageCatalog', $organization);

        $this->periods->delete($pricingPeriod);

        return response()->noContent();
    }
}
