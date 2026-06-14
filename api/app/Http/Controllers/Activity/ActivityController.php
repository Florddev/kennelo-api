<?php

declare(strict_types=1);

namespace App\Http\Controllers\Activity;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\ListActivitiesRequest;
use App\Http\Requests\Activity\StoreActivityRequest;
use App\Http\Requests\Activity\UpdateActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Services\Activity\ActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * @tags Activities
 */
class ActivityController extends Controller
{
    public function __construct(
        private ActivityService $activityService
    ) {}

    public function index(ListActivitiesRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Activity::class);

        $activities = $this->activityService->getUserActivities($request->user(), $request->validated());

        return ActivityResource::collection($activities)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function store(StoreActivityRequest $request): JsonResponse
    {
        $this->authorize('create', Activity::class);

        $activity = $this->activityService->create($request->user(), $request->validated());

        return (new ActivityResource($activity))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $activity = $this->activityService->findById($id, $request->user());

        $this->authorize('view', $activity);

        return (new ActivityResource($activity))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function update(UpdateActivityRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('update', $activity);

        $activity = $this->activityService->update($activity, $request->validated());

        return (new ActivityResource($activity))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function destroy(Activity $activity): JsonResponse
    {
        $this->authorize('delete', $activity);

        $this->activityService->delete($activity);

        return response()->json(null, 204);
    }
}
