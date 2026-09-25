<?php

declare(strict_types=1);

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agenda\ShowAgendaRequest;
use App\Http\Resources\AgendaViewResource;
use App\Models\Activity;
use App\Models\Organization;
use App\Services\Agenda\AgendaService;

/**
 * @tags Agenda
 */
class AgendaController extends Controller
{
    public function __construct(
        private readonly AgendaService $agenda,
    ) {}

    /**
     * Show the agenda
     *
     * Sur une période : les ressources, leurs rendez-vous, options placées, absences et blocages, et les options de
     * séjour acceptées qui restent à placer. Toute l'entreprise demande bookings.view sur toute l'entreprise ; avec
     * activity_id, bookings.view sur cette activité suffit.
     */
    public function show(ShowAgendaRequest $request, Organization $organization): AgendaViewResource
    {
        $activity = $request->filled('activity_id') ? Activity::query()->findOrFail($request->validated('activity_id')) : null;

        $activity === null
            ? $this->authorize('viewAgenda', $organization)
            : $this->authorize('viewBookings', $activity);

        return new AgendaViewResource($this->agenda->agenda($organization, $request->validated(), $activity));
    }
}
