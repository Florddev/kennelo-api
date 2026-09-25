<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ResourceBooking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Une plage occupée dans l'agenda. Pour une prestation réservée, booking donne la réservation, le client, l'animal
 * et la prestation ; il vaut null quand la réservation est celle d'une autre activité que celle affichée.
 *
 * @mixin ResourceBooking
 */
class ResourceBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $item = $this->relationLoaded('bookingItem') ? $this->bookingItem : null;
        $booking = $item?->booking;

        return [
            'id' => $this->id,
            'resource_id' => $this->resource_id,
            'kind' => $this->kind->value,
            'starts_at' => $this->starts_at->toISOString(),
            'ends_at' => $this->ends_at->toISOString(),
            'note' => $this->note,
            'booking' => $booking === null ? null : [
                'id' => $booking->id,
                'activity_id' => $booking->activity_id,
                'status' => $booking->status->value,
                'item_id' => $item->id,
                'client' => ['first_name' => $booking->user?->first_name, 'last_name' => $booking->user?->last_name],
                'service' => ['id' => $item->service_id, 'name' => $item->service?->name],
                'pet' => ['id' => $item->pet_id, 'name' => $item->pet?->name],
            ],
        ];
    }
}
