<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ReviewCriteriaDefinition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReviewCriteriaDefinition */
class ReviewCriteriaDefinitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'label' => $this->label,
            'applicable_to' => $this->applicable_to->value,
            'sort_order' => $this->sort_order,
        ];
    }
}
