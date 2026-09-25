<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ActivityUnitType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ActivityUnitType
 */
class ActivityUnitTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'max_animals_per_unit' => $this->max_animals_per_unit,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'animal_types' => AnimalTypeResource::collection($this->whenLoaded('animalTypes')),
        ];
    }
}
