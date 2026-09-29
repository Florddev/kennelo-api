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
            'to_schedule' => ItemToScheduleResource::collection($this->resource['to_schedule']),
        ];
    }
}
