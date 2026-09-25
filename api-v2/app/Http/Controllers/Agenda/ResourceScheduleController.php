<?php

declare(strict_types=1);

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agenda\ReplaceResourceSchedulesRequest;
use App\Http\Resources\AgendaResourceResource;
use App\Models\Activity;
use App\Models\AgendaResource;
use App\Services\Agenda\ResourceService;

/**
 * @tags Agenda
 */
class ResourceScheduleController extends Controller
{
    public function __construct(
        private readonly ResourceService $resources,
    ) {}

    /**
     * Replace the schedule of a resource in an activity
     *
     * La semaine est remplacée d'un bloc ; une liste vide retire la ressource de l'activité. Une ressource qui a un
     * planning dans l'activité y réalise toutes les prestations sur rendez-vous.
     */
    public function update(ReplaceResourceSchedulesRequest $request, Activity $activity, AgendaResource $resource): AgendaResourceResource
    {
        $this->authorize('schedule', [$activity, $resource]);

        return new AgendaResourceResource($this->resources->replaceSchedules($resource, $activity, $request->validated('schedules')));
    }
}
