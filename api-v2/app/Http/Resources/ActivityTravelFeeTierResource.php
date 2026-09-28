<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ActivityTravelFeeTier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ActivityTravelFeeTier */
class ActivityTravelFeeTierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'up_to_km' => $this->up_to_km,
            'fee' => $this->fee,
        ];
    }
}
