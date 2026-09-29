<?php

declare(strict_types=1);

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notification\ListNotificationsRequest;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Notifications
 */
class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function index(ListNotificationsRequest $request): AnonymousResourceCollection
    {
        return NotificationResource::collection(
            $this->notificationService->paginate($request->user(), $request->validated())
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['unread_count' => $this->notificationService->unreadCount($request->user())]);
    }

    public function markAsRead(Notification $notification): NotificationResource
    {
        $this->authorize('update', $notification);

        return new NotificationResource($this->notificationService->markAsRead($notification));
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        return response()->json(['marked_count' => $this->notificationService->markAllAsRead($request->user())]);
    }

    public function destroy(Notification $notification): Response
    {
        $this->authorize('delete', $notification);

        $this->notificationService->delete($notification);

        return response()->noContent();
    }
}
