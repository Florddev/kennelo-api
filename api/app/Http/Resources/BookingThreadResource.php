<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\BookingThread;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BookingThread */
class BookingThreadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'booking_id' => $this->booking_id,
            'conversation_id' => $this->conversation_id,
            'is_active' => $this->is_active,
            'archived_at' => $this->archived_at ? human_date($this->archived_at) : null,
            'booking' => new BookingResource($this->whenLoaded('booking')),
        ];
    }
}
