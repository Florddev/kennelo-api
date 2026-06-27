<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\WeekDayEnum;
use App\Models\ActivityCycleSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ActivityCycleSetting */
class ActivityCycleSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $occupiedSpots = $this->resource->occupied_spots ?? 0;

        return [
            'id' => $this->id,
            'animal_type' => [
                'id' => $this->animalType->id,
                'code' => $this->animalType->code,
                'name' => $this->animalType->name,
                'category' => $this->animalType->category,
            ],
            'max_capacity' => $this->max_capacity,
            'price' => $this->price,
            'sum_weekdays' => $this->sum_weekdays,
            'week_days' => array_map(fn (WeekDayEnum $day): int => $day->value, WeekDayEnum::fromMask($this->sum_weekdays)),
            'occupied_spots' => $this->when($request->user() !== null, $occupiedSpots),
            'available_spots' => $this->when($request->user() !== null, $this->max_capacity - $occupiedSpots),
        ];
    }
}
