<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PricingPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Une période tarifaire. Lue depuis une activité, elle porte le réglage et la grille de l'activité (setting,
 * null tant que l'activité ne l'applique pas).
 *
 * @mixin PricingPeriod
 */
class PricingPeriodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_base' => $this->isBase(),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'is_recurring' => $this->is_recurring,
            'priority' => $this->priority,
            'color' => $this->color,
            'setting' => $this->whenLoaded('activitySetting', fn () => $this->activitySetting === null
                ? null
                : ActivityPeriodSettingResource::make($this->activitySetting)),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
