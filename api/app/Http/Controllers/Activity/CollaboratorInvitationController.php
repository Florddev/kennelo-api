<?php

declare(strict_types=1);

namespace App\Http\Controllers\Activity;

use App\Enums\ApiStatusEnum;
use App\Enums\CollaboratorStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityCollaboratorResource;
use App\Models\Activity;
use App\Models\ActivityCollaborator;
use App\Models\User;
use App\Services\Activity\CollaboratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Activities
 */
class CollaboratorInvitationController extends Controller
{
    public function __construct(
        private CollaboratorService $collaboratorService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $links = $request->user()->collaboratorLinks()
            ->where('status', CollaboratorStatusEnum::PENDING->value)
            ->with(['activity.media', 'activity.manager.media', 'role.permissions'])
            ->get();

        return ActivityCollaboratorResource::collection($links)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function accept(Request $request, Activity $activity): JsonResponse
    {
        $this->ensurePendingInvitation($request->user(), $activity);

        $link = $this->collaboratorService->accept($activity, $request->user());

        return (new ActivityCollaboratorResource($link))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function decline(Request $request, Activity $activity): JsonResponse
    {
        $this->ensurePendingInvitation($request->user(), $activity);

        $link = $this->collaboratorService->decline($activity, $request->user());

        return (new ActivityCollaboratorResource($link))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    private function ensurePendingInvitation(User $user, Activity $activity): ActivityCollaborator
    {
        $link = ActivityCollaborator::query()
            ->where('activity_id', $activity->id)
            ->where('user_id', $user->id)
            ->where('status', CollaboratorStatusEnum::PENDING->value)
            ->first();

        abort_unless($link !== null, 404);

        return $link;
    }
}
