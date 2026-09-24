<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Address */
class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'line1' => $this->line1,
            'line2' => $this->line2,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'region' => $this->region,
            'department' => $this->department,
            'country' => $this->country,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
