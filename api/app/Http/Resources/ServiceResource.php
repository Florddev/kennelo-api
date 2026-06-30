<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Service
 */
class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'activity_id' => $this->activity_id,
            'animal_type_id' => $this->animal_type_id,
            'name' => $this->name,
            'description' => $this->description,
            'is_included' => $this->is_included,
            'price' => $this->price,
        ];
    }
}
