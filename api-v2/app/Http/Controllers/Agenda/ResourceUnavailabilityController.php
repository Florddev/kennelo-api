<?php

declare(strict_types=1);

namespace App\Http\Controllers\Agenda;

use App\Enums\ResourceBookingKindEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agenda\StoreUnavailabilityRequest;
use App\Http\Resources\ResourceBookingResource;
use App\Models\AgendaResource;
use App\Models\Organization;
use App\Models\ResourceBooking;
use App\Services\Agenda\AgendaService;
use Illuminate\Http\Response;

/**
 * Absences des personnes, blocages des équipements et des espaces.
 *
 * @tags Agenda
 */
class ResourceUnavailabilityController extends Controller
{
    public function __construct(
        private readonly AgendaService $agenda,
    ) {}

    /**
     * Add an absence or a block
     *
     * Elle ne peut pas recouvrir un rendez-vous ou une autre absence (409) : il faut d'abord l'annuler.
     */
    public function store(StoreUnavailabilityRequest $request, Organization $organization, AgendaResource $resource): ResourceBookingResource
    {
        $this->authorize('manageAvailability', $resource);

        return new ResourceBookingResource($this->agenda->addUnavailability($resource, $request->unavailability(), $request->user()));
    }

    /**
     * Remove an absence or a block
     */
    public function destroy(Organization $organization, AgendaResource $resource, ResourceBooking $resourceBooking): Response
    {
        $this->authorize('manageAvailability', $resource);

        abort_if($resourceBooking->kind === ResourceBookingKindEnum::BOOKING, 404, __('errors.not_found'));

        $this->agenda->removeUnavailability($resourceBooking);

        return response()->noContent();
    }
}
