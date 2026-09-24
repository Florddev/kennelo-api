<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AttributeOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AttributeOption */
class AttributeOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'value' => $this->value,
            'label' => $this->label,
            'sort_order' => $this->sort_order,
        ];
    }
}
