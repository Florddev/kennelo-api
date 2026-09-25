<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AnimalType;
use App\Models\Service;
use App\Models\ServicePrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServicePrice>
 */
class ServicePriceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'animal_type_id' => AnimalType::factory(),
            'price' => '30.00',
            'duration_minutes' => 60,
        ];
    }
}
