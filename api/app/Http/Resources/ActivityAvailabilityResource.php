<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ActivityAvailability;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ActivityAvailability */
class ActivityAvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->toDateString(),
            'status' => $this->status->value,
            'note' => $this->when($request->user() !== null, $this->note),
        ];
    }
}
