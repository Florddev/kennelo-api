<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\BookingItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BookingItem */
class ItemToScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'activity_id' => $this->booking?->activity_id,
            'start_date' => $this->booking?->start_date->toDateString(),
            'end_date' => $this->booking?->end_date->toDateString(),
            'client' => ['first_name' => $this->booking?->user?->first_name, 'last_name' => $this->booking?->user?->last_name],
            'service' => ['id' => $this->service_id, 'name' => $this->service?->name],
            'pet' => ['id' => $this->pet_id, 'name' => $this->pet?->name],
            'duration_minutes' => $this->duration_minutes,
        ];
    }
}
