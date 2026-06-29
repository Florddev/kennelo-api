<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ActivityCycle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ActivityCycle */
class ActivityCycleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'activity_id' => $this->activity_id,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'priority' => $this->priority,
            'is_active' => $this->is_active,
            'color' => $this->color,
            'settings' => ActivityCycleSettingResource::collection($this->whenLoaded('settings')),
            'closed_week_days' => ActivityCycleClosedWeekDayResource::collection($this->whenLoaded('closedWeekDays')),
            'created_at' => human_date($this->created_at),
            'updated_at' => human_date($this->updated_at),
        ];
    }
}
