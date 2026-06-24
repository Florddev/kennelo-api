<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notification;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\ListNotificationsRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function index(ListNotificationsRequest $request): JsonResponse
    {
        $notifications = $this->notificationService->paginate($request->user(), $request->validated());

        return NotificationResource::collection($notifications)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->unreadCount($request->user());

        return response()->json([
            'status' => ApiStatusEnum::SUCCESS,
            'data' => ['unread_count' => $count],
            'timestamp' => human_date(now()),
        ]);
    }

    public function markAsRead(Notification $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        $notification = $this->notificationService->markAsRead($notification);

        return (new NotificationResource($notification))
            ->additional(['status' => ApiStatusEnum::SUCCESS, 'timestamp' => human_date(now())])
            ->response();
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user());

        return response()->json([
            'status' => ApiStatusEnum::SUCCESS,
            'data' => ['marked_count' => $count],
            'timestamp' => human_date(now()),
        ]);
    }

    public function destroy(Notification $notification): JsonResponse
    {
        $this->authorize('delete', $notification);

        $this->notificationService->delete($notification);

        return response()->json([
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }
}
