<?php

declare(strict_types=1);

namespace App\Services\Conversation;

use App\Enums\MessageTypeEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\SenderTypeEnum;
use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Events\NewMessageNotification;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\BookingThread;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageFile;
use App\Models\User;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ConversationService
{
    public function __construct(
        private NotificationService $notifications,
        private NotificationRecipientResolver $recipients
    ) {}

    public function getUserConversations(User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return Conversation::with(['user', 'activity.manager', 'latestMessage.sender', 'bookingThreads.booking'])
            ->withCount(['messages as unread_count' => function ($query) use ($user): void {
                $query->where('sender_id', '!=', $user->id)
                    ->whereDoesntHave('reads', function ($q) use ($user): void {
                        $q->where('user_id', $user->id);
                    });
            }])
            ->where('user_id', $user->id)
            ->orderByDesc('last_message_at')
            ->paginate($perPage);
    }

    public function getActivityConversations(Activity $activity, User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return Conversation::with(['user', 'latestMessage.sender', 'bookingThreads.booking'])
            ->withCount(['messages as unread_count' => function ($query) use ($user): void {
                $query->where('sender_id', '!=', $user->id)
                    ->whereDoesntHave('reads', function ($q) use ($user): void {
                        $q->where('user_id', $user->id);
                    });
            }])
            ->where('activity_id', $activity->id)
            ->orderByDesc('last_message_at')
            ->paginate($perPage);
    }

    public function getOrCreateForBooking(User $user, Booking $booking): Conversation
    {
        $conversation = Conversation::firstOrCreate(
            [
                'user_id' => $booking->user_id,
                'activity_id' => $booking->activity_id,
            ],
            [
                'last_message_at' => now(),
            ]
        );

        $thread = BookingThread::firstOrCreate(
            ['booking_id' => $booking->id],
            ['conversation_id' => $conversation->id]
        );

        if ($thread->wasRecentlyCreated) {
            $this->sendMessage($user, $conversation, [
                'message_type' => MessageTypeEnum::BOOKING_REFERENCE->value,
                'booking_id' => $booking->id,
            ]);
        }

        return $conversation->load(['activity', 'user', 'bookingThreads.booking']);
    }

    public function getOrCreateForActivity(User $user, Activity $activity): Conversation
    {
        $conversation = Conversation::firstOrCreate(
            [
                'user_id' => $user->id,
                'activity_id' => $activity->id,
            ],
            [
                'last_message_at' => now(),
            ]
        );

        return $conversation->load(['activity', 'user', 'bookingThreads.booking']);
    }

    public function sendBookingReference(Conversation $conversation, User $actor, Booking $booking, ?string $message = null): void
    {
        $this->sendMessage($actor, $conversation, [
            'message_type' => MessageTypeEnum::BOOKING_REFERENCE->value,
            'booking_id' => $booking->id,
            'content' => $message,
        ]);
    }

    public function getMessages(Conversation $conversation, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 30;

        return Message::with(['sender', 'files', 'booking.activity'])
            ->where('conversation_id', $conversation->id)
            ->when(isset($filters['booking_id']), fn ($q) => $q->where('booking_id', $filters['booking_id']))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function sendMessage(User $user, Conversation $conversation, array $data): Message
    {
        return DB::transaction(function () use ($user, $conversation, $data): Message {
            $senderType = (string) $conversation->user_id === (string) $user->id
                ? SenderTypeEnum::USER
                : SenderTypeEnum::ACTIVITY;

            $messageType = isset($data['message_type'])
                ? MessageTypeEnum::from($data['message_type'])
                : MessageTypeEnum::TEXT;

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'booking_id' => $data['booking_id'] ?? null,
                'sender_id' => $user->id,
                'sender_type' => $senderType,
                'message_type' => $messageType,
                'content' => $data['content'] ?? null,
            ]);

            foreach ($data['files'] ?? [] as $uploadedFile) {
                /** @var UploadedFile $uploadedFile */
                $path = $uploadedFile->store('conversations', 'local');
                MessageFile::create([
                    'message_id' => $message->id,
                    'file_name' => $uploadedFile->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $uploadedFile->extension(),
                    'file_size' => $uploadedFile->getSize(),
                    'mime_type' => $uploadedFile->getMimeType() ?? $uploadedFile->getClientMimeType(),
                ]);
            }

            $conversation->update(['last_message_at' => now()]);

            $message->load(['sender', 'files']);
            if ($message->booking_id) {
                $message->load('booking.activity');
            }

            event(new MessageSent($message));
            event(new NewMessageNotification($message));

            if (in_array($messageType, [MessageTypeEnum::TEXT, MessageTypeEnum::FILE], true)) {
                $this->notifications->notify(
                    $this->recipients->forMessage($message),
                    NotificationTypeEnum::NEW_MESSAGE,
                    [
                        'conversation_id' => $conversation->id,
                        'message_id' => $message->id,
                        'sender_id' => $user->id,
                        'content_preview' => str($message->content ?? '')->limit(100)->toString(),
                    ],
                );
            }

            return $message;
        });
    }

    public function markAsRead(User $user, Conversation $conversation): int
    {
        $unreadMessageIds = Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('reads', function ($q) use ($user): void {
                $q->where('user_id', $user->id);
            })
            ->pluck('id');

        if ($unreadMessageIds->isEmpty()) {
            return 0;
        }

        $inserts = $unreadMessageIds->map(fn (string $id) => [
            'message_id' => $id,
            'user_id' => $user->id,
            'read_at' => now(),
        ])->all();

        DB::table('message_reads')->insertOrIgnore($inserts);

        $count = count($inserts);

        event(new MessagesRead($conversation, $user, $count));

        return $count;
    }

    public function getUnreadCount(User $user): int
    {
        $managedActivityIds = $user->managedActivities()->pluck('id');

        return Message::whereHas('conversation', function ($q) use ($user, $managedActivityIds): void {
            $q->where('user_id', $user->id)
                ->orWhereIn('activity_id', $managedActivityIds);
        })
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('reads', function ($q) use ($user): void {
                $q->where('user_id', $user->id);
            })
            ->count();
    }
}
