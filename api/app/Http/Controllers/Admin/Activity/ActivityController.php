<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Activity;

use App\Enums\AdminActionTypeEnum;
use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Activity\ListActivitiesRequest;
use App\Http\Requests\Admin\Activity\RejectActivityRequest;
use App\Http\Requests\Admin\Activity\UpdateActivityRequest;
use App\Http\Resources\AdminActivityResource;
use App\Models\Activity;
use App\Services\Admin\Activity\ActivityAdminService;
use App\Services\Admin\AdminActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * @tags Admin Activities
 */
class ActivityController extends Controller
{
    public function __construct(
        private ActivityAdminService $activities,
        private AdminActionService $actions,
    ) {}

    public function index(ListActivitiesRequest $request): JsonResponse
    {
        $this->authorize('moderate', Activity::class);

        $activities = $this->activities->paginate($request->validated());

        return AdminActivityResource::collection($activities)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function show(Activity $activity): JsonResponse
    {
        $this->authorize('moderate', Activity::class);

        $activity = $this->activities->find($activity->id);

        return $this->respond($activity);
    }

    public function approve(Activity $activity): JsonResponse
    {
        $this->authorize('moderate', Activity::class);

        $activity = $this->activities->approve($activity, request()->user());
        $this->actions->log(request()->user(), null, AdminActionTypeEnum::APPROVE_ACTIVITY, [
            'activity_id' => $activity->id,
            'activity_name' => $activity->name,
        ]);

        return $this->respond($activity, 'Activity approved successfully');
    }

    public function reject(RejectActivityRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('moderate', Activity::class);

        $activity = $this->activities->reject($activity, $request->user(), $request->validated());
        $this->actions->log($request->user(), null, AdminActionTypeEnum::REJECT_ACTIVITY, [
            'activity_id' => $activity->id,
            'reason' => $request->validated()['reason'],
        ]);

        return $this->respond($activity, 'Activity rejected successfully');
    }

    public function update(UpdateActivityRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('moderate', Activity::class);

        $activity = $this->activities->update($activity, $request->validated());
        $this->actions->log($request->user(), null, AdminActionTypeEnum::UPDATE_ACTIVITY, [
            'activity_id' => $activity->id,
        ]);

        return $this->respond($activity, 'Activity updated successfully');
    }

    public function verifyCompany(Activity $activity): JsonResponse
    {
        $this->authorize('moderate', Activity::class);

        $activity = $this->activities->verifyCompany($activity);

        return $this->respond($activity, 'Company verification completed');
    }

    private function respond(Activity $activity, ?string $message = null): JsonResponse
    {
        $additional = [
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(Carbon::now()),
        ];

        if ($message !== null) {
            $additional['message'] = $message;
        }

        return (new AdminActivityResource($activity))
            ->additional($additional)
            ->response();
    }
}
