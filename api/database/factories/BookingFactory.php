<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Establishment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        $checkIn = fake()->dateTimeBetween('+1 day', '+30 days');
        $checkOut = fake()->dateTimeBetween($checkIn, '+60 days');
        $totalPrice = fake()->randomFloat(2, 50, 500);
        $platformFee = round($totalPrice * 0.10, 2);

        return [
            'user_id' => User::factory(),
            'establishment_id' => Establishment::factory(),
            'check_in_date' => $checkIn->format('Y-m-d'),
            'check_out_date' => $checkOut->format('Y-m-d'),
            'total_price' => $totalPrice,
            'platform_fee' => $platformFee,
            'establishment_amount' => round($totalPrice - $platformFee, 2),
            'status' => BookingStatus::PENDING,
            'payment_status' => 'pending',
            'special_requests' => fake()->optional(0.4)->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => BookingStatus::PENDING]);
    }

    public function confirmed(): static
    {
        return $this->state(['status' => BookingStatus::CONFIRMED]);
    }

    public function completed(): static
    {
        return $this->state(['status' => BookingStatus::COMPLETED]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => BookingStatus::CANCELLED]);
    }
}
