<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AttributeDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AttributeDefinition */
class AttributeDefinitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'label' => $this->label,
            'category' => $this->category,
            'value_type' => $this->value_type,
            'has_predefined_options' => $this->has_predefined_options,
            'is_required' => $this->is_required,
            'options' => $this->has_predefined_options
                ? AttributeOptionResource::collection($this->whenLoaded('options'))
                : null,
        ];
    }
}
