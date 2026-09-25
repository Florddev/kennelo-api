<?php

declare(strict_types=1);

namespace App\Http\Controllers\Activity;

use App\Enums\AvailabilityStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\ListAvailabilitiesRequest;
use App\Http\Requests\Activity\ReplaceOpeningHoursRequest;
use App\Http\Requests\Activity\StoreAvailabilitiesRequest;
use App\Http\Resources\ActivityAvailabilityResource;
use App\Http\Resources\ActivityOpeningHourResource;
use App\Models\Activity;
use App\Models\ActivityAvailability;
use App\Services\Activity\ActivityAvailabilityService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Activity schedule
 */
class ActivityScheduleController extends Controller
{
    public function __construct(
        private readonly ActivityAvailabilityService $availabilities,
    ) {}

    /**
     * Replace the opening hours
     *
     * La semaine complète est remplacée d'un bloc. Plusieurs plages par jour sont possibles.
     */
    public function updateOpeningHours(ReplaceOpeningHoursRequest $request, Activity $activity): AnonymousResourceCollection
    {
        $this->authorize('update', $activity);

        return ActivityOpeningHourResource::collection(
            $this->availabilities->replaceOpeningHours($activity, $request->validated('hours')),
        );
    }

    /**
     * List the exceptions to the opening hours
     *
     * Fermetures et ouvertures exceptionnelles sur la période demandée.
     */
    public function indexAvailabilities(ListAvailabilitiesRequest $request, Activity $activity): AnonymousResourceCollection
    {
        $this->authorize('update', $activity);

        return ActivityAvailabilityResource::collection(
            $this->availabilities->exceptions($activity, $request->validated('from'), $request->validated('to')),
        );
    }

    /**
     * Close or open exceptionally
     *
     * Pose la même exception sur une liste de dates ou sur chaque jour d'une période. Une date déjà renseignée est remplacée.
     */
    public function storeAvailabilities(StoreAvailabilitiesRequest $request, Activity $activity): AnonymousResourceCollection
    {
        $this->authorize('update', $activity);

        return ActivityAvailabilityResource::collection($this->availabilities->store(
            $activity,
            $request->dates(),
            AvailabilityStatusEnum::from($request->validated('status')),
            $request->validated('note'),
        ));
    }

    /**
     * Remove an exception
     *
     * La date reprend les horaires habituels.
     */
    public function destroyAvailability(Activity $activity, ActivityAvailability $availability): Response
    {
        $this->authorize('update', $activity);

        $this->availabilities->delete($availability);

        return response()->noContent();
    }
}
