<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PricingPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Par défaut, une période saisonnière : l'été de l'année prochaine.
 *
 * @extends Factory<PricingPeriod>
 */
class PricingPeriodFactory extends Factory
{
    public function definition(): array
    {
        $year = now()->addYear()->year;

        return [
            'organization_id' => Organization::factory(),
            'name' => 'Été',
            'start_date' => "{$year}-07-01",
            'end_date' => "{$year}-08-31",
        ];
    }

    public function between(string $start, string $end): static
    {
        return $this->state(fn (): array => ['start_date' => $start, 'end_date' => $end]);
    }

    public function recurring(): static
    {
        return $this->state(fn (): array => ['is_recurring' => true]);
    }
}
