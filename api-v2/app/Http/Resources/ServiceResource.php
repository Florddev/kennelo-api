<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Prestation du catalogue de l'entreprise, avec sa grille de prix de base.
 *
 * @mixin Service
 */
class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_package' => $this->is_package,
            'requires_scheduling' => $this->requires_scheduling,
            'is_active' => $this->is_active,
            'prices' => ServicePriceResource::collection($this->whenLoaded('prices')),
            'package_items' => $this->whenLoaded('packageItems', fn () => $this->packageItems->map(
                fn (Service $item): array => ['id' => $item->id, 'name' => $item->name],
            )),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
