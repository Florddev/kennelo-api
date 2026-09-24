<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AnimalType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AnimalType */
class AnimalTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'category' => $this->category,
            'attribute_definitions' => AttributeDefinitionResource::collection($this->whenLoaded('attributeDefinitions')),
        ];
    }
}
