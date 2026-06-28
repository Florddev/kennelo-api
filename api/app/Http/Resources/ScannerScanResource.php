<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ScannerScan;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ScannerScan */
class ScannerScanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'microchip_number' => $this->microchip_number,
            'found' => $this->found,
            'scanned_at' => human_date($this->scanned_at),
            'scanner' => $this->whenLoaded('scanner', fn () => [
                'id' => $this->scanner->id,
                'code' => $this->scanner->code,
                'name' => $this->scanner->name,
            ]),
            'pet' => $this->whenLoaded('pet', fn () => $this->pet === null ? null : [
                'id' => $this->pet->id,
                'name' => $this->pet->name,
                'avatar_url' => $this->pet->getFirstMediaUrl(MediaService::COLLECTION_AVATAR, MediaService::CONVERSION_AVATAR_WEBP)
                    ?: $this->pet->getFirstMediaUrl(MediaService::COLLECTION_AVATAR)
                    ?: null,
                'animal_type' => $this->pet->animalType === null
                    ? null
                    : new AnimalTypeResource($this->pet->animalType),
            ]),
        ];
    }
}
