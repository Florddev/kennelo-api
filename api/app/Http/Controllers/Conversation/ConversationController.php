<?php

declare(strict_types=1);

namespace App\Http\Controllers\Conversation;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Conversation\ListConversationsRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Pet;
use App\Models\User;
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
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function show(Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->load(['user', 'activity', 'latestMessage.sender', 'bookingThreads.booking']);

        return (new ConversationResource($conversation))
            ->additional(['status' => ApiStatusEnum::SUCCESS, 'timestamp' => human_date(now())])
            ->response();
    }

    public function storeForBooking(Request $request, Booking $booking): JsonResponse
    {
        $user = $request->user();
        $booking->loadMissing('activity');

        $isGuest = (string) $booking->user_id === (string) $user->id;
        $isHost = $booking->activity && (string) $booking->activity->manager_id === (string) $user->id;

        abort_if(! $isGuest && ! $isHost, 403);

        $conversation = $this->conversationService->getOrCreateForBooking($user, $booking);

        return (new ConversationResource($conversation))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response()
            ->setStatusCode(200);
    }

    public function storeForPet(Request $request, Pet $pet): JsonResponse
    {
        $user = $request->user();

        abort_if((string) $pet->user_id === (string) $user->id, 403);

        $activityId = $request->input('activity_id');

        if ($activityId) {
            $activity = Activity::findOrFail($activityId);
            $this->authorize('manageForActivity', [Conversation::class, $activity]);
        } else {
            $activity = $user->managedActivities()->first();
            abort_if($activity === null, 403);
            assert($activity instanceof Activity);
        }

        $petOwner = User::findOrFail($pet->user_id);
        $conversation = $this->conversationService->getOrCreateForPetOwner($petOwner, $activity);

        return (new ConversationResource($conversation))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response()
            ->setStatusCode(200);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->conversationService->getUnreadCount($request->user());

        return response()->json([
            'status' => ApiStatusEnum::SUCCESS,
            'data' => ['unread_count' => $count],
            'timestamp' => human_date(now()),
        ]);
    }
}
