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
            'name' => $this->pet?->name,
            'species' => $this->pet?->animalType?->name,
            'breed' => $this->pet?->breed,
        ];
    }

    public function broadcastAs(): string
    {
        return 'pet.broadcast';
    }
}
