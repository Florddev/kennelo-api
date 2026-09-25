<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Service;
use App\Models\ServicePrice;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Prestation telle qu'une activité la vend : ses conditions de vente, et sa grille avec l'ajustement
 * de l'activité déjà appliqué. pet_price n'apparaît que si le client a choisi un animal.
 *
 * @mixin Service
 */
class OfferedServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $adjustment = $this->offer->adjustment_percent;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'is_package' => $this->is_package,
            'requires_scheduling' => $this->requires_scheduling,
            'is_active' => $this->is_active,
            'offer' => [
                'offered_as' => $this->offer->offered_as->value,
                'adjustment_percent' => $adjustment,
                'is_included' => $this->offer->is_included,
                'is_active' => $this->offer->is_active,
            ],
            'prices' => $this->whenLoaded('prices', fn () => $this->prices->map(fn (ServicePrice $line): array => [
                ...ServicePriceResource::make($line)->resolve($request),
                'price' => Money::adjust($line->price, $adjustment),
            ])),
            'pet_price' => $this->when(
                array_key_exists('pet_price', $this->resource->getAttributes()),
                fn (): mixed => $this->resource->getAttribute('pet_price'),
            ),
        ];
    }
}
