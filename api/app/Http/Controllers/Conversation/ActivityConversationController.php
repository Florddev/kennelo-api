<?php

declare(strict_types=1);

namespace App\Http\Controllers\Conversation;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Conversation\ListConversationsRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Activity;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Conversation\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityConversationController extends Controller
{
    public function __construct(
        private ConversationService $conversationService
    ) {}

    public function index(ListConversationsRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('manageForActivity', [Conversation::class, $activity]);

        $conversations = $this->conversationService->getActivityConversations(
            $activity,
            $request->user(),
            $request->validated()
        );

        return ConversationResource::collection($conversations)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function store(Request $request, Activity $activity): JsonResponse
    {
        $user = $request->user();
        $targetUserId = $request->input('user_id');

        if ($targetUserId) {
            $this->authorize('manageForActivity', [Conversation::class, $activity]);
            $targetUser = User::findOrFail($targetUserId);
        } else {
            abort_if((string) $activity->manager_id === (string) $user->id, 403);
            $targetUser = $user;
        }

        $conversation = $this->conversationService->getOrCreateForActivity($targetUser, $activity);

        return (new ConversationResource($conversation))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response()
            ->setStatusCode(200);
    }
}
