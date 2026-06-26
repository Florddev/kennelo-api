<?php

declare(strict_types=1);

namespace App\Http\Controllers\Activity;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\BulkAvailabilityRequest;
use App\Http\Requests\Activity\ListAvailabilitiesRequest;
use App\Http\Requests\Activity\RangeAvailabilitiesRequest;
use App\Http\Requests\Activity\StoreAvailabilityRequest;
use App\Http\Requests\Activity\UpdateAvailabilityRequest;
use App\Http\Resources\ActivityAvailabilityResource;
use App\Models\Activity;
use App\Models\ActivityAvailability;
use App\Services\Activity\ActivityAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * @tags Activities
 */
class ActivityAvailabilityController extends Controller
{
    public function __construct(private ActivityAvailabilityService $service) {}

    public function index(ListAvailabilitiesRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('viewAvailabilities', $activity);

        $availabilities = $this->service->getCalendar($activity, $request->validated('month'));

        return ActivityAvailabilityResource::collection($availabilities)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function range(RangeAvailabilitiesRequest $request, Activity $activity): JsonResponse
    {
        $availabilities = $this->service->getRange(
            $activity,
            $request->validated('start_date'),
            $request->validated('end_date'),
        );

        return ActivityAvailabilityResource::collection($availabilities)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function store(StoreAvailabilityRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('manageAvailabilities', $activity);

        $availabilities = $this->service->storePeriod($activity, $request->validated());

        return ActivityAvailabilityResource::collection($availabilities)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function bulk(BulkAvailabilityRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('manageAvailabilities', $activity);

        $availabilities = $this->service->bulk($activity, $request->validated());

        return ActivityAvailabilityResource::collection($availabilities)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function update(UpdateAvailabilityRequest $request, Activity $activity, ActivityAvailability $availability): JsonResponse
    {
        $this->authorize('manageAvailabilities', $activity);
        abort_if($availability->activity_id !== $activity->id, 404);

        $availability = $this->service->update($availability, $request->validated());

        return (new ActivityAvailabilityResource($availability))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function destroy(Activity $activity, ActivityAvailability $availability): JsonResponse
    {
        $this->authorize('manageAvailabilities', $activity);
        abort_if($availability->activity_id !== $activity->id, 404);

        $this->service->delete($availability);

        return response()->json(null, 204);
    }
}
