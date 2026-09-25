<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ResourceBookingKindEnum;
use App\Models\AgendaResource;
use App\Models\ResourceBooking;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Une absence d'une heure, demain.
 *
 * @extends Factory<ResourceBooking>
 */
class ResourceBookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'resource_id' => AgendaResource::factory(),
            'kind' => ResourceBookingKindEnum::ABSENCE,
            'starts_at' => now()->addDay()->startOfHour(),
            'ends_at' => now()->addDay()->startOfHour()->addHour(),
        ];
    }

    /**
     * Instants ISO 8601, avec leur fuseau.
     */
    public function between(string $start, string $end): static
    {
        return $this->state(fn (): array => [
            'starts_at' => CarbonImmutable::parse($start)->utc(),
            'ends_at' => CarbonImmutable::parse($end)->utc(),
        ]);
    }
}
