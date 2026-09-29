<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AgendaResource;
use App\Models\ResourceSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource de l'agenda, avec ses plannings par activité, vue par l'équipe.
 *
 * @mixin AgendaResource
 */
class AgendaResourceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'member' => $this->whenLoaded('member', fn (): ?array => $this->member === null ? null : [
                'id' => $this->member->id,
                'user_id' => $this->member->user_id,
                'first_name' => $this->member->user?->first_name,
                'last_name' => $this->member->user?->last_name,
            ]),
            'schedules' => $this->whenLoaded('schedules', fn (): array => $this->schedules->map(fn (ResourceSchedule $schedule): array => [
                'activity_id' => $schedule->activity_id,
                'weekday' => $schedule->weekday,
                'start_time' => substr($schedule->start_time, 0, 5),
                'end_time' => substr($schedule->end_time, 0, 5),
            ])->all()),
        ];
    }
}
