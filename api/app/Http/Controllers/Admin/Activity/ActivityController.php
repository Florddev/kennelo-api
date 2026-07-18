<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Activity;

use App\Enums\AdminActionTypeEnum;
use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Activity\LinkActivityGoogleRequest;
use App\Http\Requests\Admin\Activity\ListActivitiesRequest;
use App\Http\Requests\Admin\Activity\RejectActivityRequest;
use App\Http\Requests\Admin\Activity\UpdateActivityRequest;
use App\Http\Resources\AdminActivityResource;
use App\Models\Activity;
use App\Services\Admin\Activity\ActivityAdminService;
use App\Services\Admin\Activity\ActivityGoogleService;
use App\Services\Admin\AdminActionService;
use Illuminate\Http\JsonResponse;

/**
 * @tags Admin Activities
 */
class ActivityController extends Controller
{
    public function __construct(
        private ActivityAdminService $activities,
        private ActivityGoogleService $google,
        private AdminActionService $actions,
    ) {}

    public function index(ListActivitiesRequest $request): JsonResponse
    {
        $this->authorize('moderate', Activity::class);

        $activities = $this->activities->paginate($request->validated());

        return AdminActivityResource::collection($activities)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
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

    public function searchGoogle(Activity $activity): JsonResponse
    {
        $this->authorize('moderate', Activity::class);

        $candidate = $this->google->searchGoogle($activity);

        return response()->json([
            'data' => $candidate,
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }

    public function linkGoogle(LinkActivityGoogleRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('moderate', Activity::class);

        $activity = $this->google->linkGoogle($activity, $request->validated());
        $this->actions->log($request->user(), null, AdminActionTypeEnum::UPDATE_ACTIVITY, [
            'activity_id' => $activity->id,
            'google_place_id' => $activity->google_place_id,
        ]);

        return $this->respond($activity, 'Activity linked to Google successfully');
    }

    public function unlinkGoogle(Activity $activity): JsonResponse
    {
        $this->authorize('moderate', Activity::class);

        $activity = $this->google->unlinkGoogle($activity);

        return $this->respond($activity, 'Activity unlinked from Google successfully');
    }

    private function respond(Activity $activity, ?string $message = null): JsonResponse
    {
        $additional = [
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ];

        if ($message !== null) {
            $additional['message'] = $message;
        }

        return (new AdminActivityResource($activity))
            ->additional($additional)
            ->response();
    }
}
