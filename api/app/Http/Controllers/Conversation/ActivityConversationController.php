<?php

declare(strict_types=1);

namespace App\Http\Controllers\Conversation;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Conversation\ListConversationsRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Activity;
use App\Models\Conversation;
use App\Services\Conversation\ConversationService;
use Illuminate\Http\JsonResponse;

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
}
