<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hosting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hosting\ListInCarePetsRequest;
use App\Http\Resources\InCarePetResource;
use App\Models\Organization;
use App\Services\Hosting\InCarePetService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Bookings
 */
class InCarePetController extends Controller
{
    public function __construct(private readonly InCarePetService $pets) {}

    /**
     * List the pets in care
     *
     * Animaux des réservations confirmées ou en cours qui couvrent aujourd'hui, dans les activités où je vois les
     * réservations, avec leur propriétaire. Avec microchip, retrouve un animal scanné parmi eux : une puce inconnue
     * ou d'un animal qui n'est pas confié à l'entreprise ne donne rien.
     */
    public function index(ListInCarePetsRequest $request, Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('viewInCarePets', $organization);

        return InCarePetResource::collection($this->pets->forOrganization($organization, $request->user(), $request->validated('microchip')));
    }
}
