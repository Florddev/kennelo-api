<?php

declare(strict_types=1);

namespace App\Http\Controllers\Conversation;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Conversation\ListMessagesRequest;
use App\Http\Requests\Conversation\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Services\Conversation\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(
        private ConversationService $conversationService
    ) {}

    public function index(ListMessagesRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $messages = $this->conversationService->getMessages($conversation, $request->validated());

        return MessageResource::collection($messages)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function store(StoreMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('sendMessage', $conversation);

        $message = $this->conversationService->sendMessage($request->user(), $conversation, $request->validated());

        return (new MessageResource($message))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function markAsRead(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $count = $this->conversationService->markAsRead($request->user(), $conversation);

        return response()->json([
            'status' => ApiStatusEnum::SUCCESS,
            'data' => ['marked_count' => $count],
            'timestamp' => human_date(now()),
        ]);
    }
}
