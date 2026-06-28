<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Pet;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PetBroadcast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $microchipNumber,
        public string $userId,
        public string $scannerCode,
        public ?Pet $pet = null,
    ) {}

    /** @return array<int, PrivateChannel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->userId),
        ];
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'microchip_number' => $this->microchipNumber,
            'scanner_code' => $this->scannerCode,
            'found' => $this->pet !== null,
            'pet' => $this->pet ? [
                'id' => $this->pet->id,
                'animal_type_id' => $this->pet->animal_type_id,
                'name' => $this->pet->name,
                'breed' => $this->pet->breed,
                'birth_date' => $this->pet->birth_date?->toDateString(),
                'sex' => $this->pet->sex,
                'weight' => $this->pet->weight,
                'is_sterilized' => $this->pet->is_sterilized,
                'has_microchip' => $this->pet->has_microchip,
                'microchip_number' => $this->pet->microchip_number,
                'about' => $this->pet->about,
                'avatar_url' => $this->pet->getFirstMediaUrl('avatar') ?: null,
                'animal_type' => $this->pet->animalType ? [
                    'id' => $this->pet->animalType->id,
                    'code' => $this->pet->animalType->code,
                    'name' => $this->pet->animalType->name,
                    'category' => $this->pet->animalType->category,
                ] : null,
                'created_at' => $this->pet->created_at?->toISOString(),
                'updated_at' => $this->pet->updated_at?->toISOString(),
            ] : null,
        ];
    }

    public function broadcastAs(): string
    {
        return 'pet.broadcast';
    }
}
