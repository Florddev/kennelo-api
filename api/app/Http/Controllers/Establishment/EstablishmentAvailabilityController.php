<?php

declare(strict_types=1);

namespace App\Http\Controllers\Establishment;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Establishment\BulkAvailabilityRequest;
use App\Http\Requests\Establishment\ListAvailabilitiesRequest;
use App\Http\Requests\Establishment\RangeAvailabilitiesRequest;
use App\Http\Requests\Establishment\StoreAvailabilityRequest;
use App\Http\Requests\Establishment\UpdateAvailabilityRequest;
use App\Http\Resources\EstablishmentAvailabilityResource;
use App\Models\Establishment;
use App\Models\EstablishmentAvailability;
use App\Services\Establishment\EstablishmentAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * @tags Establishments
 */
class EstablishmentAvailabilityController extends Controller
{
    public function __construct(private EstablishmentAvailabilityService $service) {}

    public function index(ListAvailabilitiesRequest $request, Establishment $establishment): JsonResponse
    {
        $this->authorize('viewAvailabilities', $establishment);

        $availabilities = $this->service->getCalendar($establishment, $request->validated('month'));

        return EstablishmentAvailabilityResource::collection($availabilities)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function range(RangeAvailabilitiesRequest $request, Establishment $establishment): JsonResponse
    {
        $this->authorize('viewAvailabilities', $establishment);

        $availabilities = $this->service->getRange(
            $establishment,
            $request->validated('start_date'),
            $request->validated('end_date'),
        );

        return EstablishmentAvailabilityResource::collection($availabilities)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function store(StoreAvailabilityRequest $request, Establishment $establishment): JsonResponse
    {
        $this->authorize('manageAvailabilities', $establishment);

        $availabilities = $this->service->storePeriod($establishment, $request->validated());

        return EstablishmentAvailabilityResource::collection($availabilities)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function bulk(BulkAvailabilityRequest $request, Establishment $establishment): JsonResponse
    {
        $this->authorize('manageAvailabilities', $establishment);

        $availabilities = $this->service->bulk($establishment, $request->validated());

        return EstablishmentAvailabilityResource::collection($availabilities)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function update(UpdateAvailabilityRequest $request, Establishment $establishment, EstablishmentAvailability $availability): JsonResponse
    {
        $this->authorize('manageAvailabilities', $establishment);
        abort_if($availability->establishment_id !== $establishment->id, 404);

        $availability = $this->service->update($availability, $request->validated());

        return (new EstablishmentAvailabilityResource($availability))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function destroy(Establishment $establishment, EstablishmentAvailability $availability): JsonResponse
    {
        $this->authorize('manageAvailabilities', $establishment);
        abort_if($availability->establishment_id !== $establishment->id, 404);

        $this->service->delete($availability);

        return response()->json(null, 204);
    }
}
