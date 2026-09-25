<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ActivityOpeningHour;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ActivityOpeningHour */
class ActivityOpeningHourResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // PostgreSQL renvoie les heures avec les secondes (09:00:00) : on répond toujours en HH:MM.
        return [
            'weekday' => $this->weekday->value,
            'opens_at' => substr($this->opens_at, 0, 5),
            'closes_at' => substr($this->closes_at, 0, 5),
        ];
    }
}
