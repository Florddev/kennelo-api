<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\SearchLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SearchLog */
class SearchLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'location' => $this->location,
            'department' => $this->department,
            'region' => $this->region,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'filters' => $this->filters,
            'results_count' => $this->results_count,
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'email' => $this->user->email,
            ] : null),
            'created_at' => human_date($this->created_at),
        ];
    }
}
