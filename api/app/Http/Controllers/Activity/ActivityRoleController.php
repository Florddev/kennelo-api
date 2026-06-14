<?php

declare(strict_types=1);

namespace App\Http\Controllers\Activity;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\StoreActivityRoleRequest;
use App\Http\Requests\Activity\UpdateActivityRoleRequest;
use App\Http\Resources\ActivityRoleResource;
use App\Models\Activity;
use App\Models\ActivityRole;
use App\Services\Activity\ActivityRoleService;
use Illuminate\Http\JsonResponse;

/**
 * @tags Activities
 */
class ActivityRoleController extends Controller
{
    public function __construct(
        private ActivityRoleService $roleService
    ) {}

    public function index(Activity $activity): JsonResponse
    {
        $this->authorize('manageCollaborators', $activity);

        $roles = $activity->roles()->with('permissions')->get();

        return ActivityRoleResource::collection($roles)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function store(StoreActivityRoleRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('manageCollaborators', $activity);

        $role = $this->roleService->create($activity, $request->validated());

        return (new ActivityRoleResource($role))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateActivityRoleRequest $request, Activity $activity, ActivityRole $role): JsonResponse
    {
        $this->authorize('manageCollaborators', $activity);
        $this->ensureRoleBelongsToActivity($activity, $role);

        $role = $this->roleService->update($role, $request->validated());

        return (new ActivityRoleResource($role))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function destroy(Activity $activity, ActivityRole $role): JsonResponse
    {
        $this->authorize('manageCollaborators', $activity);
        $this->ensureRoleBelongsToActivity($activity, $role);

        if ($role->collaborators()->exists()) {
            abort(422, 'This role is still assigned to collaborators.');
        }

        $this->roleService->delete($role);

        return response()->json(null, 204);
    }

    private function ensureRoleBelongsToActivity(Activity $activity, ActivityRole $role): void
    {
        if ($role->activity_id !== $activity->id) {
            abort(404);
        }
    }
}
