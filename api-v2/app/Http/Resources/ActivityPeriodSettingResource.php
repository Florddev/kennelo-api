<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\WeekDayEnum;
use App\Models\ActivityPeriodPrice;
use App\Models\ActivityPeriodSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ActivityPeriodSetting
 */
class ActivityPeriodSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'pricing_period_id' => $this->pricing_period_id,
            'is_active' => $this->is_active,
            'min_stay' => $this->min_stay,
            'price_modifier_percent' => $this->price_modifier_percent,
            'closed_weekdays' => array_map(fn (WeekDayEnum $day): int => $day->value, WeekDayEnum::fromMask($this->closed_weekdays)),
            'prices' => $this->whenLoaded('prices', fn (): array => $this->prices->map(fn (ActivityPeriodPrice $price): array => [
                'unit_type_id' => $price->activity_unit_type_id,
                'weekday' => $price->weekday?->value,
                'price' => $price->price,
                'extra_animal_price' => $price->extra_animal_price,
            ])->all()),
        ];
    }
}
