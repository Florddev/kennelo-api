<?php

declare(strict_types=1);

namespace App\Http\Controllers\Activity;

use App\Enums\ApiStatusEnum;
use App\Enums\CollaboratorStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\AssignCollaboratorRoleRequest;
use App\Http\Requests\Activity\InviteCollaboratorRequest;
use App\Http\Resources\ActivityCollaboratorResource;
use App\Models\Activity;
use App\Models\ActivityCollaborator;
use App\Models\ActivityRole;
use App\Models\User;
use App\Services\Activity\CollaboratorService;
use Illuminate\Http\JsonResponse;

/**
 * @tags Activities
 */
class ActivityCollaboratorController extends Controller
{
    public function __construct(
        private CollaboratorService $collaboratorService
    ) {}

    public function index(Activity $activity): JsonResponse
    {
        $this->authorize('manageCollaborators', $activity);

        $links = $activity->collaboratorLinks()->with(['user.media', 'role.permissions'])->get();

        return ActivityCollaboratorResource::collection($links)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function store(InviteCollaboratorRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('manageCollaborators', $activity);

        $user = User::where('email', $request->validated('email'))->firstOrFail();

        abort_if($user->id === $activity->manager_id, 422, 'The manager already manages this activity.');

        $existing = ActivityCollaborator::query()
            ->where('activity_id', $activity->id)
            ->where('user_id', $user->id)
            ->first();

        abort_if(
            $existing !== null && $existing->status !== CollaboratorStatusEnum::REFUSED,
            422,
            'This user is already a collaborator or has a pending invitation.'
        );

        $link = $this->collaboratorService->invite($activity, $user);

        return (new ActivityCollaboratorResource($link))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function assignRole(AssignCollaboratorRoleRequest $request, Activity $activity, User $user): JsonResponse
    {
        $this->authorize('manageCollaborators', $activity);

        $role = ActivityRole::findOrFail($request->validated('role_id'));
        abort_if($role->activity_id !== $activity->id, 404);

        $link = ActivityCollaborator::query()
            ->where('activity_id', $activity->id)
            ->where('user_id', $user->id)
            ->first();
        abort_unless($link !== null, 404);
        abort_unless(
            $link->status === CollaboratorStatusEnum::ACCEPTED,
            422,
            'The collaborator must accept the invitation before a role can be assigned.'
        );

        $link = $this->collaboratorService->assignRole($activity, $user, $role);

        return (new ActivityCollaboratorResource($link))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function destroy(Activity $activity, User $user): JsonResponse
    {
        $this->authorize('manageCollaborators', $activity);

        abort_unless($activity->collaboratorLinks()->where('user_id', $user->id)->exists(), 404);

        $this->collaboratorService->remove($activity, $user);

        return response()->json(null, 204);
    }
}
