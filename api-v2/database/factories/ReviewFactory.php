<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BookingStatusEnum;
use App\Enums\ReviewerTypeEnum;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Par défaut, l'avis encore caché du client sur une réservation terminée.
 *
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory()->confirmed()->status(BookingStatusEnum::COMPLETED),
            'activity_id' => fn (array $attributes): ?string => Booking::query()->whereKey($attributes['booking_id'])->value('activity_id'),
            'reviewer_id' => fn (array $attributes): ?string => Booking::query()->whereKey($attributes['booking_id'])->value('user_id'),
            'reviewer_type' => ReviewerTypeEnum::USER,
            'overall_rating' => '4.5',
            'comment' => fake()->sentence(),
            'would_recommend' => true,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['is_published' => true, 'published_at' => now()]);
    }

    public function rating(string $rating): static
    {
        return $this->state(fn (): array => ['overall_rating' => $rating]);
    }

    /**
     * L'avis de l'équipe sur le client, donné par $memberId.
     */
    public function aboutClient(string $memberId): static
    {
        return $this->state(fn (): array => ['reviewer_id' => $memberId, 'reviewer_type' => ReviewerTypeEnum::ACTIVITY]);
    }
}
