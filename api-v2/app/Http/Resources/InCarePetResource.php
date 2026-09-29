<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Booking;
use App\Models\Pet;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un animal confié à l'entreprise, avec la réservation qui le couvre et son propriétaire, joignable pendant la
 * garde.
 *
 * @mixin Pet
 */
class InCarePetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Booking $booking */
        $booking = $this->resource->getRelation('careBooking');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'animal_type' => AnimalTypeResource::make($this->animalType),
            'animal_breed' => AnimalBreedResource::make($this->whenLoaded('animalBreed')),
            'has_microchip' => $this->has_microchip,
            'microchip_number' => $this->microchip_number,
            'avatar_url' => $this->getFirstMediaUrl(MediaService::COLLECTION_AVATAR, MediaService::CONVERSION_AVATAR_WEBP) ?: null,
            'owner' => $booking->user === null ? null : [
                'id' => $booking->user->id,
                'first_name' => $booking->user->first_name,
                'last_name' => $booking->user->last_name,
                'phone' => $booking->user->phone,
            ],
            'booking' => [
                'id' => $booking->id,
                'status' => $booking->status,
                'activity' => ['id' => $booking->activity_id, 'name' => $booking->activity?->name],
                'start_date' => $booking->start_date->toDateString(),
                'end_date' => $booking->end_date->toDateString(),
            ],
        ];
    }
}
