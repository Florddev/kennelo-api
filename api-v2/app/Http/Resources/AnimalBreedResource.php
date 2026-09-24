<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AnimalBreed;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AnimalBreed */
class AnimalBreedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'animal_type_id' => $this->animal_type_id,
            'breed' => $this->breed,
            'label' => $this->label,
        ];
    }
}
