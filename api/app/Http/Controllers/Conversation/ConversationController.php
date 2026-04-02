<?php

declare(strict_types=1);

namespace App\Http\Controllers\Conversation;

use App\Enums\ApiStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Conversation\ListConversationsRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Booking;
use App\Models\Conversation;
use App\Services\Conversation\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function __construct(
        private ConversationService $conversationService
    ) {}

    public function index(ListConversationsRequest $request): JsonResponse
    {
        $conversations = $this->conversationService->getUserConversations($request->user(), $request->validated());

        return ConversationResource::collection($conversations)
            ->additional([
                'status' => ApiStatus::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function show(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->load(['user', 'establishment', 'latestMessage.sender', 'bookingThreads.booking']);

        return (new ConversationResource($conversation))
            ->additional(['status' => ApiStatus::SUCCESS, 'timestamp' => human_date(now())])
            ->response();
    }

    public function storeForBooking(Request $request, Booking $booking): JsonResponse
    {
        abort_if((string) $booking->user_id !== (string) $request->user()->id, 403);

        $conversation = $this->conversationService->getOrCreateForBooking($request->user(), $booking);

        return (new ConversationResource($conversation))
            ->additional([
                'status' => ApiStatus::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response()
            ->setStatusCode(200);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->conversationService->getUnreadCount($request->user());

        return response()->json([
            'status' => ApiStatus::SUCCESS,
            'data' => ['unread_count' => $count],
            'timestamp' => human_date(now()),
        ]);
    }
}
