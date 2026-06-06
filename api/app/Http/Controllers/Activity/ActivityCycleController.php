<?php

declare(strict_types=1);

namespace App\Http\Controllers\Activity;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\StoreActivityCycleRequest;
use App\Http\Requests\Activity\UpdateActivityCycleRequest;
use App\Http\Requests\Activity\UpsertClosedWeekDaysRequest;
use App\Http\Requests\Activity\UpsertCycleSettingsRequest;
use App\Http\Resources\ActivityCycleResource;
use App\Models\Activity;
use App\Models\ActivityCycle;
use App\Services\Activity\ActivityCycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * @tags Activities
 */
class ActivityCycleController extends Controller
{
    public function __construct(private ActivityCycleService $service) {}

    public function index(Activity $activity): JsonResponse
    {
        $this->authorize('viewCycles', $activity);

        $cycles = $this->service->list($activity);

        return ActivityCycleResource::collection($cycles)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function store(StoreActivityCycleRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('manageCycles', $activity);

        $cycle = $this->service->createCycle($activity, $request->validated());

        return (new ActivityCycleResource($cycle))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateActivityCycleRequest $request, Activity $activity, ActivityCycle $cycle): JsonResponse
    {
        $this->authorize('manageCycles', $activity);
        abort_if($cycle->activity_id !== $activity->id, 404);

        $cycle = $this->service->updateCycle($cycle, $request->validated());

        return (new ActivityCycleResource($cycle))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function destroy(Activity $activity, ActivityCycle $cycle): JsonResponse
    {
        $this->authorize('manageCycles', $activity);
        abort_if($cycle->activity_id !== $activity->id, 404);

        $this->service->deleteCycle($cycle);

        return response()->json(null, 204);
    }

    public function settings(UpsertCycleSettingsRequest $request, Activity $activity, ActivityCycle $cycle): JsonResponse
    {
        $this->authorize('manageCycles', $activity);
        abort_if($cycle->activity_id !== $activity->id, 404);

        $cycle = $this->service->upsertSettings($cycle, $request->validated()['settings']);

        return (new ActivityCycleResource($cycle))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function closedWeekDays(UpsertClosedWeekDaysRequest $request, Activity $activity, ActivityCycle $cycle): JsonResponse
    {
        $this->authorize('manageCycles', $activity);
        abort_if($cycle->activity_id !== $activity->id, 404);

        $cycle = $this->service->upsertClosedWeekDays($cycle, (int) $request->validated()['sum_weekdays']);

        return (new ActivityCycleResource($cycle))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }
}
