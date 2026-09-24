<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\NotificationTypeEnum;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(NotificationTypeEnum::cases()),
            'data' => [],
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (): array => ['read_at' => now()]);
    }

    public function ofType(NotificationTypeEnum $type): static
    {
        return $this->state(fn (): array => ['type' => $type]);
    }
}
