<?php

declare(strict_types=1);

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agenda\ListSlotsRequest;
use App\Models\Activity;
use App\Models\AgendaResource;
use App\Models\Pet;
use App\Services\Booking\AppointmentService;
use Illuminate\Http\JsonResponse;

/**
 * @tags Agenda
 */
class SlotController extends Controller
{
    public function __construct(
        private readonly AppointmentService $appointments,
    ) {}

    /**
     * List the free slots of a service
     *
     * Public. Avec pet_ids (animaux du client connecté), le créneau dure le temps de les faire tous, l'un après
     * l'autre ; sans, le temps le plus long de la grille. Chaque créneau donne les ressources libres (resource_ids),
     * décrites dans meta.resources. Les heures sont en UTC ; meta.timezone est le fuseau de l'activité.
     */
    public function index(ListSlotsRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('view', $activity);

        $pets = Pet::query()->whereKey($request->validated('pet_ids', []))->with(['animalType', 'animalBreed'])->get();
        // Dans l'ordre de la demande : c'est celui du rendez-vous.
        $pets = array_map(fn (string $id): Pet => $pets->findOrFail($id), $request->validated('pet_ids', []));

        $result = $this->appointments->slots(
            $activity,
            $request->validated('service_id'),
            $pets,
            $request->date('from')->toImmutable(),
            $request->date('to')->toImmutable(),
            $request->validated('resource_id'),
        );

        return response()->json([
            'data' => array_map(fn (array $slot): array => [
                'starts_at' => $slot['starts_at']->toISOString(),
                'ends_at' => $slot['ends_at']->toISOString(),
                'resource_ids' => $slot['resource_ids'],
            ], $result['slots']),
            'meta' => [
                'timezone' => $activity->timezone,
                'duration_minutes' => $result['duration_minutes'],
                'resources' => $result['resources']->map(fn (AgendaResource $resource): array => [
                    'id' => $resource->id,
                    'type' => $resource->type->value,
                    'name' => $resource->name,
                ])->all(),
            ],
        ]);
    }
}
