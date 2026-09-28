<?php

declare(strict_types=1);

namespace App\Services\Conversation;

use App\Enums\MessageSenderTypeEnum;
use App\Enums\OrganizationPermissionEnum;
use App\Events\Conversation\MessagesRead;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\BookingThread;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageRead;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Conversations entre les clients et les équipes des activités.
 *
 * Un message est « non lu » pour une personne tant qu'elle ne l'a pas marqué lu et qu'il vient de l'autre côté
 * de la conversation : pour le client, ce qu'écrit l'équipe ; pour chaque membre de l'équipe, ce qu'écrit le
 * client. Les messages système doublent les notifications de réservation : ils ne comptent pas.
 */
class ConversationService
{
    /**
     * Conversations du client qui ont au moins un message, les plus récentes d'abord.
     *
     * @param  array<string, mixed>  $filters
     */
    public function forClient(User $client, array $filters = []): LengthAwarePaginator
    {
        return $this->listed($client->conversations()->getQuery(), $client, MessageSenderTypeEnum::ACTIVITY)
            ->with('activity.media')
            ->paginate($filters['per_page'] ?? null);
    }

    /**
     * Boîte de réception de l'équipe d'une activité.
     *
     * @param  array<string, mixed>  $filters
     */
    public function forActivity(Activity $activity, User $member, array $filters = []): LengthAwarePaginator
    {
        return $this->listed(Conversation::query()->where('activity_id', $activity->id), $member, MessageSenderTypeEnum::USER)
            ->with('user.media')
            ->paginate($filters['per_page'] ?? null);
    }

    public function show(Conversation $conversation, User $viewer): Conversation
    {
        return $conversation
            ->load(['user.media', 'activity.media', 'latestMessage.sender.media', 'threads.booking'])
            ->loadCount(['messages as unread_count' => fn (Builder $messages) => $this->unread($messages, $viewer, $this->otherSide($conversation, $viewer))]);
    }

    /**
     * La conversation du client avec l'activité, ouverte si besoin.
     */
    public function open(User $client, Activity $activity): Conversation
    {
        return Conversation::query()->firstOrCreate(['user_id' => $client->id, 'activity_id' => $activity->id]);
    }

    /**
     * La conversation du client de la réservation avec l'activité, et le fil de la réservation.
     */
    public function forBooking(Booking $booking): Conversation
    {
        return DB::transaction(function () use ($booking): Conversation {
            $conversation = Conversation::query()->firstOrCreate(['user_id' => $booking->user_id, 'activity_id' => $booking->activity_id]);
            BookingThread::query()->firstOrCreate(['booking_id' => $booking->id], ['conversation_id' => $conversation->id]);

            return $conversation;
        });
    }

    /**
     * Archive le fil d'une réservation terminée.
     */
    public function archive(Booking $booking): void
    {
        BookingThread::query()
            ->whereKey($booking->id)
            ->whereNull('archived_at')
            ->update(['archived_at' => now()]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function messages(Conversation $conversation, array $filters = []): LengthAwarePaginator
    {
        return $conversation->messages()
            ->with(['sender.media', 'files'])
            // Lu par l'autre côté : le client pour un message de l'équipe, un membre pour un message du client.
            ->withExists(['reads as is_read' => fn (Builder $reads) => $reads->whereColumn('message_reads.user_id', '!=', 'messages.sender_id')])
            ->when($filters['booking_id'] ?? null, fn (Builder $messages, string $bookingId) => $messages->where('booking_id', $bookingId))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 30);
    }

    /**
     * Marque lus les messages de l'autre côté ; renvoie leur nombre.
     */
    public function markAsRead(Conversation $conversation, User $reader): int
    {
        $unread = $this->unread($conversation->messages()->getQuery(), $reader, $this->otherSide($conversation, $reader))->pluck('id');

        if ($unread->isEmpty()) {
            return 0;
        }

        $readAt = now();
        MessageRead::query()->insertOrIgnore($unread->map(fn (string $id): array => [
            'message_id' => $id,
            'user_id' => $reader->id,
            'read_at' => $readAt,
        ])->all());

        MessagesRead::dispatch($conversation, $reader, $unread->count());

        return $unread->count();
    }

    /**
     * Messages non lus de toutes les conversations de la personne : les siennes en tant que client, et celles des
     * activités où elle a messages.reply.
     */
    public function unreadCount(User $user): int
    {
        $asClient = Conversation::query()->select('id')->where('user_id', $user->id);
        $asTeam = Conversation::query()->select('id')->whereIn(
            'activity_id',
            Activity::query()->allowing($user, OrganizationPermissionEnum::MESSAGES_REPLY)->select('activities.id'),
        );

        return Message::query()
            ->whereDoesntHave('reads', fn (Builder $reads) => $reads->where('user_id', $user->id))
            ->where(fn (Builder $messages) => $messages
                ->where(fn (Builder $messages) => $messages->where('sender_type', MessageSenderTypeEnum::ACTIVITY)->whereIn('conversation_id', $asClient))
                ->orWhere(fn (Builder $messages) => $messages->where('sender_type', MessageSenderTypeEnum::USER)->whereIn('conversation_id', $asTeam)))
            ->count();
    }

    public function isLatestUnread(string $messageId, User $reader): bool
    {
        $message = Message::query()->find($messageId);

        if ($message === null || $message->reads()->where('user_id', $reader->id)->exists()) {
            return false;
        }

        return Message::query()
            ->where('conversation_id', $message->conversation_id)
            ->where('sender_type', $message->sender_type)
            ->where(fn (Builder $newer) => $newer
                ->where('created_at', '>', $message->created_at)
                ->orWhere(fn (Builder $sameTime) => $sameTime->where('created_at', $message->created_at)->where('id', '>', $message->id)))
            ->doesntExist();
    }

    /**
     * @param  Builder<Conversation>  $query
     * @return Builder<Conversation>
     */
    private function listed(Builder $query, User $viewer, MessageSenderTypeEnum $unreadFrom): Builder
    {
        return $query
            ->whereNotNull('last_message_at')
            ->with(['latestMessage.sender.media', 'threads.booking'])
            ->withCount(['messages as unread_count' => fn (Builder $messages) => $this->unread($messages, $viewer, $unreadFrom)])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');
    }

    /**
     * @param  Builder<Message>  $messages
     * @return Builder<Message>
     */
    private function unread(Builder $messages, User $reader, MessageSenderTypeEnum $from): Builder
    {
        return $messages
            ->where('sender_type', $from)
            ->whereDoesntHave('reads', fn (Builder $reads) => $reads->where('user_id', $reader->id));
    }

    /**
     * Côté dont la personne lit les messages.
     */
    private function otherSide(Conversation $conversation, User $viewer): MessageSenderTypeEnum
    {
        return $conversation->isClient($viewer) ? MessageSenderTypeEnum::ACTIVITY : MessageSenderTypeEnum::USER;
    }
}
