<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\MessageTypeEnum;
use App\Models\Message;
use App\Models\MessageFile;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un message. Celui d'un membre de l'équipe porte son auteur et sender_type « activity » ; un message système n'a
 * pas d'auteur : system_event donne l'événement de réservation, content sa phrase dans la langue de la requête.
 * is_read dit si l'autre côté l'a lu.
 *
 * @mixin Message
 */
class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isSystem = $this->message_type === MessageTypeEnum::SYSTEM;

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'booking_id' => $this->booking_id,
            'sender_type' => $this->sender_type,
            'sender' => $this->whenLoaded('sender', fn (): ?array => $this->sender === null ? null : [
                'id' => $this->sender->id,
                'first_name' => $this->sender->first_name,
                'last_name' => $this->sender->last_name,
                'avatar_url' => $this->sender->relationLoaded('media')
                    ? $this->sender->getFirstMediaUrl(MediaService::COLLECTION_AVATAR, MediaService::CONVERSION_AVATAR_WEBP) ?: null
                    : null,
            ]),
            'type' => $this->message_type,
            'content' => $isSystem ? __('conversations.system.'.$this->content) : $this->content,
            'system_event' => $this->when($isSystem, fn (): ?string => $this->content),
            'files' => $this->whenLoaded('files', fn (): array => $this->files->map(fn (MessageFile $file): array => [
                'id' => $file->id,
                'file_name' => $file->file_name,
                'file_type' => $file->file_type,
                'file_size' => $file->file_size,
                'mime_type' => $file->mime_type,
                'url' => route('conversations.files.show', ['conversation' => $this->conversation_id, 'file' => $file->id]),
            ])->all()),
            'is_read' => $this->when(! $isSystem && array_key_exists('is_read', $this->resource->getAttributes()), fn (): bool => (bool) $this->resource->getAttribute('is_read')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
