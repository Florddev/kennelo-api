<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Pet;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Pet */
class PetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'animal_type_id' => $this->animal_type_id,
            'name' => $this->name,
            'breed' => $this->breed,
            'birth_date' => $this->birth_date?->toDateString(),
            'sex' => $this->sex,
            'weight' => $this->weight,
            'is_sterilized' => $this->is_sterilized,
            'has_microchip' => $this->has_microchip,
            'microchip_number' => $this->microchip_number,
            'adoption_date' => $this->adoption_date?->toDateString(),
            'about' => $this->about,
            'avatar_url' => $this->getFirstMediaUrl(MediaService::COLLECTION_AVATAR, MediaService::CONVERSION_AVATAR_WEBP)
                ?: $this->getFirstMediaUrl(MediaService::COLLECTION_AVATAR)
                ?: null,
            'health_notes' => $this->health_notes,
            'animal_type' => new AnimalTypeResource($this->whenLoaded('animalType')),
            'attributes' => PetAttributeResource::collection($this->whenLoaded('petAttributes')),
            'images' => PetImageResource::collection($this->getMedia(MediaService::COLLECTION_IMAGES)),
            'created_at' => human_date($this->created_at),
            'updated_at' => human_date($this->updated_at),
        ];
    }
}
