<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AgendaResource;
use App\Models\BookingItem;
use App\Models\ResourceBooking;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vue agenda du pro sur une période : les ressources, leurs plages occupées, et les options de séjour acceptées
 * qui restent à placer.
 *
 * @property array{timezone: string, resources: Collection<int, AgendaResource>, entries: Collection<int, ResourceBooking>, to_schedule: Collection<int, BookingItem>} $resource
 */
class AgendaViewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'timezone' => $this->resource['timezone'],
            'resources' => AgendaResourceResource::collection($this->resource['resources']),
            'entries' => ResourceBookingResource::collection($this->resource['entries']),
            'to_schedule' => $this->resource['to_schedule']->map(fn (BookingItem $item): array => [
                'id' => $item->id,
                'booking_id' => $item->booking_id,
                'activity_id' => $item->booking?->activity_id,
                'start_date' => $item->booking?->start_date->toDateString(),
                'end_date' => $item->booking?->end_date->toDateString(),
                'client' => ['first_name' => $item->booking?->user?->first_name, 'last_name' => $item->booking?->user?->last_name],
                'service' => ['id' => $item->service_id, 'name' => $item->service?->name],
                'pet' => ['id' => $item->pet_id, 'name' => $item->pet?->name],
                'duration_minutes' => $item->duration_minutes,
            ])->all(),
        ];
    }
}
