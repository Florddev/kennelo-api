<?php

declare(strict_types=1);

namespace App\Services\Conversation;

use App\Enums\MessageType;
use App\Enums\SenderType;
use App\Events\MessageSent;
use App\Events\MessagesRead;
use App\Events\NewMessageNotification;
use App\Models\Booking;
use App\Models\BookingThread;
use App\Models\Conversation;
use App\Models\Establishment;
use App\Models\Message;
use App\Models\MessageFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ConversationService
{
    public function getUserConversations(User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return Conversation::with(['user', 'establishment.manager', 'latestMessage.sender', 'bookingThreads.booking'])
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

    public function getEstablishmentConversations(Establishment $establishment, User $user, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 15;

        return Conversation::with(['user', 'latestMessage.sender', 'bookingThreads.booking'])
            ->withCount(['messages as unread_count' => function ($query) use ($user): void {
                $query->where('sender_id', '!=', $user->id)
                    ->whereDoesntHave('reads', function ($q) use ($user): void {
                        $q->where('user_id', $user->id);
                    });
            }])
            ->where('establishment_id', $establishment->id)
            ->orderByDesc('last_message_at')
            ->paginate($perPage);
    }

    public function getOrCreateForBooking(User $user, Booking $booking): Conversation
    {
        $conversation = Conversation::firstOrCreate(
            [
                'user_id' => $booking->user_id,
                'establishment_id' => $booking->establishment_id,
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
                'message_type' => MessageType::BookingReference->value,
                'booking_id' => $booking->id,
            ]);
        }

        return $conversation->load(['establishment', 'user', 'bookingThreads.booking']);
    }

    public function sendBookingReference(Conversation $conversation, User $actor, Booking $booking): void
    {
        $this->sendMessage($actor, $conversation, [
            'message_type' => MessageType::BookingReference->value,
            'booking_id' => $booking->id,
        ]);
    }

    public function getMessages(Conversation $conversation, array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 30;

        return Message::with(['sender', 'files', 'booking.establishment'])
            ->where('conversation_id', $conversation->id)
            ->when(isset($filters['booking_id']), fn ($q) => $q->where('booking_id', $filters['booking_id']))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function sendMessage(User $user, Conversation $conversation, array $data): Message
    {
        return DB::transaction(function () use ($user, $conversation, $data): Message {
            $senderType = (string) $conversation->user_id === (string) $user->id
                ? SenderType::User
                : SenderType::Establishment;

            $messageType = isset($data['message_type'])
                ? MessageType::from($data['message_type'])
                : MessageType::Text;

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
                $path = $uploadedFile->store('conversations', 'public');
                MessageFile::create([
                    'message_id' => $message->id,
                    'file_name' => $uploadedFile->getClientOriginalName(),
                    'file_path' => Storage::disk('public')->url($path),
                    'file_type' => $uploadedFile->extension(),
                    'file_size' => $uploadedFile->getSize(),
                    'mime_type' => $uploadedFile->getMimeType() ?? $uploadedFile->getClientMimeType(),
                ]);
            }

            $conversation->update(['last_message_at' => now()]);

            $message->load(['sender', 'files']);
            if ($message->booking_id) {
                $message->load('booking.establishment');
            }

            event(new MessageSent($message));
            event(new NewMessageNotification($message));

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
        $managedEstablishmentIds = $user->managedEstablishments()->pluck('id');

        return Message::whereHas('conversation', function ($q) use ($user, $managedEstablishmentIds): void {
            $q->where('user_id', $user->id)
                ->orWhereIn('establishment_id', $managedEstablishmentIds);
        })
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('reads', function ($q) use ($user): void {
                $q->where('user_id', $user->id);
            })
            ->count();
    }
}
