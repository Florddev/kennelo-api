<?php

declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\ListActivityServicesRequest;
use App\Http\Requests\Catalog\UpsertActivityOfferRequest;
use App\Http\Resources\OfferedServiceResource;
use App\Models\Activity;
use App\Models\Pet;
use App\Models\Service;
use App\Services\Catalog\ActivityOfferService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Offre d'une activité : les prestations du catalogue de l'entreprise qu'elle vend.
 *
 * @tags Services
 */
class ActivityServiceController extends Controller
{
    public function __construct(
        private readonly ActivityOfferService $offers,
    ) {}

    /**
     * List the services of an activity
     *
     * Grille ajustée par l'activité. Avec pet_id, chaque prestation porte le prix et la durée pour cet animal.
     * L'équipe voit aussi les prestations retirées de la vente.
     */
    public function index(ListActivityServicesRequest $request, Activity $activity): AnonymousResourceCollection
    {
        $this->authorize('view', $activity);

        $pet = $request->filled('pet_id') ? Pet::query()->findOrFail($request->validated('pet_id')) : null;

        return OfferedServiceResource::collection($this->offers->offers(
            $activity,
            $pet,
            withInactive: $request->user()?->can('update', $activity) ?? false,
        ));
    }

    /**
     * Offer a service
     *
     * Ajoute la prestation à l'offre de l'activité, ou en remplace les conditions.
     */
    public function update(UpsertActivityOfferRequest $request, Activity $activity, Service $service): OfferedServiceResource
    {
        $this->authorize('offer', [$activity, $service]);

        return new OfferedServiceResource($this->offers->offer($activity, $service, $request->validated()));
    }

    /**
     * Withdraw a service
     *
     * La prestation reste au catalogue de l'entreprise et dans les réservations passées.
     */
    public function destroy(Activity $activity, Service $service): Response
    {
        $this->authorize('offer', [$activity, $service]);

        $this->offers->withdraw($activity, $service);

        return response()->noContent();
    }
}
