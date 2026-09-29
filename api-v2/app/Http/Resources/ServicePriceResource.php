<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ServicePrice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServicePrice */
class ServicePriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'animal_type_id' => $this->animal_type_id,
            'size_class' => $this->size_class,
            'coat_type' => $this->coat_type,
            'animal_breed_id' => $this->animal_breed_id,
            'price' => $this->price,
            'duration_minutes' => $this->duration_minutes,
        ];
    }
}
