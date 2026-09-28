<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\BookingThread;
use App\Models\Conversation;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Une conversation : le client, l'activité, le dernier message, le nombre de messages non lus de la personne
 * connectée, et les réservations qui y ont leur fil (actives ou archivées).
 *
 * @mixin Conversation
 */
class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client' => $this->whenLoaded('user', fn (): ?array => $this->user === null ? null : [
                'id' => $this->user->id,
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
                'avatar_url' => $this->user->relationLoaded('media')
                    ? $this->user->getFirstMediaUrl(MediaService::COLLECTION_AVATAR, MediaService::CONVERSION_AVATAR_WEBP) ?: null
                    : null,
            ]),
            'client_id' => $this->user_id,
            'activity' => $this->whenLoaded('activity', fn (): ?array => $this->activity === null ? null : [
                'id' => $this->activity->id,
                'name' => $this->activity->name,
                'image' => $this->activity->relationLoaded('media')
                    ? $this->activity->getFirstMediaUrl(MediaService::COLLECTION_IMAGES) ?: null
                    : null,
            ]),
            'activity_id' => $this->activity_id,
            'last_message' => MessageResource::make($this->whenLoaded('latestMessage')),
            'unread_count' => $this->when(array_key_exists('unread_count', $this->resource->getAttributes()), fn (): int => (int) $this->resource->getAttribute('unread_count')),
            'bookings' => $this->whenLoaded('threads', fn (): array => $this->threads->map(fn (BookingThread $thread): array => [
                'id' => $thread->booking_id,
                'status' => $thread->booking?->status->value,
                'start_date' => $thread->booking?->start_date->toDateString(),
                'end_date' => $thread->booking?->end_date->toDateString(),
                'is_active' => $thread->isActive(),
                'archived_at' => $thread->archived_at?->toISOString(),
            ])->all()),
            'last_message_at' => $this->last_message_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
