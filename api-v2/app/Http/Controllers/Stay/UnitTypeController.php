<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stay;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stay\StoreUnitTypeRequest;
use App\Http\Requests\Stay\UpdateUnitTypeRequest;
use App\Http\Resources\ActivityUnitTypeResource;
use App\Models\Activity;
use App\Models\ActivityUnitType;
use App\Services\Stay\UnitTypeService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * Places d'un séjour : boxes, chambres, paddocks…
 *
 * @tags Unit types
 */
class UnitTypeController extends Controller
{
    public function __construct(
        private readonly UnitTypeService $unitTypes,
    ) {}

    /**
     * List the units of an activity
     *
     * Publique pour une activité réservable. L'équipe voit aussi les places désactivées.
     */
    public function index(Request $request, Activity $activity): AnonymousResourceCollection
    {
        $this->authorize('view', $activity);

        return ActivityUnitTypeResource::collection($this->unitTypes->forActivity(
            $activity,
            withInactive: $request->user()?->can('update', $activity) ?? false,
        ));
    }

    /**
     * Create a unit
     */
    public function store(StoreUnitTypeRequest $request, Activity $activity): ActivityUnitTypeResource
    {
        $this->authorize('update', $activity);

        return new ActivityUnitTypeResource($this->unitTypes->create($activity, $request->validated()));
    }

    /**
     * Update a unit
     */
    public function update(UpdateUnitTypeRequest $request, Activity $activity, ActivityUnitType $unitType): ActivityUnitTypeResource
    {
        $this->authorize('update', $activity);

        return new ActivityUnitTypeResource($this->unitTypes->update($unitType, $request->validated()));
    }

    /**
     * Delete a unit
     *
     * Une place déjà réservée ne se supprime pas : désactivez-la.
     */
    public function destroy(Activity $activity, ActivityUnitType $unitType): Response
    {
        $this->authorize('update', $activity);

        $this->unitTypes->delete($unitType);

        return response()->noContent();
    }
}
