<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\ReviewerType;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory()->state(['status' => BookingStatus::COMPLETED]),
            'reviewer_id' => User::factory(),
            'reviewer_type' => ReviewerType::USER,
            'overall_rating' => fake()->randomFloat(1, 3, 5),
            'comment' => fake()->optional(0.7)->paragraph(),
            'private_feedback' => fake()->optional(0.3)->sentence(),
            'would_recommend' => fake()->boolean(85),
            'is_published' => false,
            'published_at' => null,
        ];
    }

    public function fromUser(): static
    {
        return $this->state(['reviewer_type' => ReviewerType::USER]);
    }

    public function fromEstablishment(): static
    {
        return $this->state(['reviewer_type' => ReviewerType::ESTABLISHMENT]);
    }

    public function published(): static
    {
        return $this->state([
            'is_published' => true,
            'published_at' => now(),
        ]);
    }
}
