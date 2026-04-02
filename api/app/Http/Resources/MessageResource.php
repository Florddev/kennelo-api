<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Message */
class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'booking_id' => $this->booking_id,
            'sender_id' => $this->sender_id,
            'sender_type' => $this->sender_type->value,
            'message_type' => $this->message_type->value,
            'content' => $this->content,
            'sender' => new UserResource($this->whenLoaded('sender')),
            'files' => MessageFileResource::collection($this->whenLoaded('files')),
            'created_at' => human_date($this->created_at),
        ];
    }
}
