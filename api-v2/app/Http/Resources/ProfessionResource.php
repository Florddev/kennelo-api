<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\LocationModeEnum;
use App\Models\Profession;
use App\Models\ProfessionDocumentRequirement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Profession */
class ProfessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'category' => ProfessionCategoryResource::make($this->whenLoaded('category')),
            'booking_mode' => $this->booking_mode->value,
            'billing_unit' => $this->billing_unit->value,
            'locations' => array_map(fn (LocationModeEnum $location): string => $location->value, $this->allowedLocations()),
            'pricing_dimensions' => $this->pricing_dimensions ?? [],
            'animal_types' => AnimalTypeResource::collection($this->whenLoaded('animalTypes')),
            'document_requirements' => $this->whenLoaded('documentRequirements', fn () => $this->documentRequirements->map(
                fn (ProfessionDocumentRequirement $requirement): array => [
                    'type' => $requirement->document_type->value,
                    'is_required' => $requirement->is_required,
                    'validity_months' => $requirement->validity_months,
                ],
            )),
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'activities_count' => $this->whenCounted('activities'),
        ];
    }
}
