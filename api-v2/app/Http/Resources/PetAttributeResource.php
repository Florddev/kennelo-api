<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AttributeDefinition;
use App\Models\PetAttribute;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PetAttribute */
class PetAttributeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attribute_definition_id' => $this->attribute_definition_id,
            'attribute_option_id' => $this->attribute_option_id,
            'value' => $this->resolveValue(),
            'attribute_definition' => new AttributeDefinitionResource($this->whenLoaded('attributeDefinition')),
            'attribute_option' => new AttributeOptionResource($this->whenLoaded('attributeOption')),
        ];
    }

    private function resolveValue(): mixed
    {
        /** @var AttributeDefinition|null $definition */
        $definition = $this->relationLoaded('attributeDefinition') ? $this->attributeDefinition : null;
        $valueType = $definition?->value_type;

        return match ($valueType) {
            'integer' => $this->value_integer,
            'decimal' => $this->value_decimal,
            'boolean' => $this->value_boolean,
            'date' => $this->value_date?->toDateString(),
            default => $this->value_text,
        };
    }
}
