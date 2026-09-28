<?php

declare(strict_types=1);

namespace App\Services\Conversation;

use App\Enums\MessageSenderTypeEnum;
use App\Enums\MessageTypeEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\OrganizationPermissionEnum;
use App\Events\Conversation\MessageSent;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Envoi des messages : ceux des personnes, et les messages système postés sur les événements de réservation.
 * Chaque message est diffusé en temps réel sur le canal de sa conversation, après le commit.
 */
class MessageService
{
    public function __construct(
        private readonly ConversationService $conversations,
        private readonly NotificationService $notifications,
        private readonly NotificationRecipientResolver $recipients,
    ) {}

    /**
     * Le type se déduit du contenu : à propos d'une réservation, avec des pièces jointes, ou du texte. Les pièces
     * jointes sont écrites avant la transaction, et effacées si elle échoue.
     *
     * @param  array{content?: string|null, booking_id?: string|null, files?: list<UploadedFile>}  $data
     */
    public function send(User $sender, Conversation $conversation, array $data): Message
    {
        $files = $this->store($conversation, $data['files'] ?? []);

        try {
            $message = DB::transaction(fn (): Message => $this->post($conversation, [
                'booking_id' => $data['booking_id'] ?? null,
                'sender_id' => $sender->id,
                'sender_type' => $conversation->isClient($sender) ? MessageSenderTypeEnum::USER : MessageSenderTypeEnum::ACTIVITY,
                'message_type' => match (true) {
                    ($data['booking_id'] ?? null) !== null => MessageTypeEnum::BOOKING_REFERENCE,
                    $files !== [] => MessageTypeEnum::FILE,
                    default => MessageTypeEnum::TEXT,
                },
                'content' => $data['content'] ?? null,
            ], $files));
        } catch (Throwable $exception) {
            Storage::disk((string) config('conversations.attachments_disk'))->delete(array_column($files, 'file_path'));

            throw $exception;
        }

        $this->notifyRecipients($message, $conversation, $sender);

        return $message->load(['sender.media', 'files']);
    }

    /**
     * Message système sur un événement de réservation, dans le fil de la réservation (ouvert si besoin). Un même
     * événement n'est posté qu'une fois : une nouvelle tentative du listener n'ajoute rien.
     */
    public function postSystem(Booking $booking, string $event): ?Message
    {
        return DB::transaction(function () use ($booking, $event): ?Message {
            $conversation = $this->conversations->forBooking($booking);

            $posted = $conversation->messages()
                ->where('booking_id', $booking->id)
                ->where('message_type', MessageTypeEnum::SYSTEM)
                ->where('content', $event)
                ->exists();

            return $posted ? null : $this->post($conversation, [
                'booking_id' => $booking->id,
                'sender_id' => null,
                'sender_type' => MessageSenderTypeEnum::SYSTEM,
                'message_type' => MessageTypeEnum::SYSTEM,
                'content' => $event,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<array<string, mixed>>  $files
     */
    private function post(Conversation $conversation, array $attributes, array $files = []): Message
    {
        $message = $conversation->messages()->create($attributes);
        $message->files()->createMany($files);
        $conversation->forceFill(['last_message_at' => $message->created_at])->save();

        MessageSent::dispatch($message);

        return $message;
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return list<array{file_name: string, file_path: string, file_type: string, file_size: int, mime_type: string}>
     */
    private function store(Conversation $conversation, array $files): array
    {
        return array_map(fn (UploadedFile $file): array => [
            // Le nom d'origine n'est qu'un libellé : ni chemin, ni caractère de contrôle.
            'file_name' => Str::of($file->getClientOriginalName())->basename()->replaceMatches('/[\x00-\x1F\x7F]/', '')->limit(255, '')->toString(),
            'file_path' => (string) $file->store("conversations/{$conversation->id}", (string) config('conversations.attachments_disk')),
            'file_type' => (string) ($file->extension() ?: $file->getClientOriginalExtension()),
            'file_size' => (int) $file->getSize(),
            'mime_type' => (string) ($file->getMimeType() ?? $file->getClientMimeType()),
        ], $files);
    }

    /**
     * Le client est prévenu d'un message de l'équipe ; les membres qui ont messages.reply, d'un message du client.
     */
    private function notifyRecipients(Message $message, Conversation $conversation, User $sender): void
    {
        $conversation->loadMissing(['activity.organization', 'user']);
        $activity = $conversation->activity;

        $recipients = $conversation->isClient($sender)
            ? ($activity?->organization === null ? collect() : $this->recipients->membersAllowedTo(OrganizationPermissionEnum::MESSAGES_REPLY, $activity->organization, $activity->id))
            : collect([$conversation->user])->filter();

        $this->notifications->notify($recipients->reject(fn (User $user): bool => $user->id === $sender->id)->values(), NotificationTypeEnum::NEW_MESSAGE, [
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'activity_id' => $conversation->activity_id,
            'activity_name' => $activity?->name,
            'sender_name' => trim($sender->first_name.' '.$sender->last_name),
            'content_preview' => Str::limit((string) $message->content, 100),
        ]);
    }
}
