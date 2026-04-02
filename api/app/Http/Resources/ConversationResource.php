<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Conversation */
class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'establishment_id' => $this->establishment_id,
            'last_message_at' => $this->last_message_at ? human_date($this->last_message_at) : null,
            'user' => new UserResource($this->whenLoaded('user')),
            'establishment' => new EstablishmentResource($this->whenLoaded('establishment')),
            'latest_message' => new MessageResource($this->whenLoaded('latestMessage')),
            'unread_count' => $this->whenHas('unread_count'),
            'booking_threads' => BookingThreadResource::collection($this->whenLoaded('bookingThreads')),
            'created_at' => human_date($this->created_at),
            'updated_at' => human_date($this->updated_at),
        ];
    }
}
