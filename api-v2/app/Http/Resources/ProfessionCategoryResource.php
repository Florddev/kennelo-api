<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ProfessionCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProfessionCategory */
class ProfessionCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'sort_order' => $this->sort_order,
            'professions' => ProfessionResource::collection($this->whenLoaded('professions')),
            'professions_count' => $this->whenCounted('professions'),
        ];
    }
}
