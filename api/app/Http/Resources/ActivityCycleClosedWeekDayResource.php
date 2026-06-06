<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\WeekDayEnum;
use App\Models\ActivityCycleClosedWeekDay;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ActivityCycleClosedWeekDay */
class ActivityCycleClosedWeekDayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sum_weekdays' => $this->sum_weekdays,
            'week_days' => array_map(fn (WeekDayEnum $day): int => $day->value, WeekDayEnum::fromMask($this->sum_weekdays)),
        ];
    }
}
