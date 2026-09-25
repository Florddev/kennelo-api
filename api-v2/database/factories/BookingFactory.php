<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BookingStatusEnum;
use App\Enums\CancellationPolicyEnum;
use App\Enums\LocationModeEnum;
use App\Enums\PaymentKindEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Activity;
use App\Models\ActivityUnitType;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Par défaut, une demande de deux nuits dans dix jours, dont le paiement est autorisé (60 € de prestations,
 * 4,80 € de frais Kennelo, 4,80 € de commission). Le paiement initial est créé avec elle.
 *
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory()->bookable(),
            'organization_id' => fn (array $attributes): string => Activity::query()->whereKey($attributes['activity_id'])->value('organization_id'),
            'user_id' => User::factory(),
            'start_date' => today()->addDays(10)->toDateString(),
            'end_date' => today()->addDays(12)->toDateString(),
            'location_mode' => LocationModeEnum::AT_PRO,
            'status' => BookingStatusEnum::PENDING,
            'currency' => 'EUR',
            'total_price' => '64.80',
            'service_fee' => '4.80',
            'platform_fee' => '4.80',
            'activity_amount' => '55.20',
            'travel_fee' => '0.00',
            'vat_rate' => '20.00',
            'payment_status' => PaymentStatusEnum::REQUIRES_CAPTURE,
            'cancellation_policy' => CancellationPolicyEnum::FLEXIBLE,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Booking $booking): void {
            $captured = in_array($booking->payment_status, [PaymentStatusEnum::SUCCEEDED, PaymentStatusEnum::PARTIALLY_REFUNDED, PaymentStatusEnum::REFUNDED], true);

            $booking->payments()->create([
                'kind' => PaymentKindEnum::INITIAL,
                'amount' => $booking->total_price,
                'currency' => $booking->currency,
                'status' => $captured ? PaymentStatusEnum::SUCCEEDED : $booking->payment_status,
                'stripe_payment_intent_id' => 'pi_'.fake()->unique()->bothify('????????????'),
                'stripe_charge_id' => $captured ? 'ch_'.fake()->unique()->bothify('????????????') : null,
                'paid_at' => $captured ? now() : null,
            ]);
        });
    }

    /**
     * Acceptée par le pro : le paiement initial est capturé.
     */
    public function confirmed(): static
    {
        return $this->state(fn (): array => [
            'status' => BookingStatusEnum::CONFIRMED,
            'payment_status' => PaymentStatusEnum::SUCCEEDED,
        ]);
    }

    public function status(BookingStatusEnum $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }

    public function between(string $start, string $end): static
    {
        return $this->state(fn (): array => ['start_date' => $start, 'end_date' => $end]);
    }

    /**
     * Séjour qui occupe $count places de ce type, pour son activité.
     */
    public function occupying(ActivityUnitType $unitType, int $count = 1): static
    {
        return $this->state(fn (): array => ['activity_id' => $unitType->activity_id])
            ->afterCreating(function (Booking $booking) use ($unitType, $count): void {
                foreach (range(1, $count) as $ignored) {
                    $booking->units()->create([
                        'activity_unit_type_id' => $unitType->id,
                        'quantity' => 1,
                        'nights' => (int) $booking->start_date->diffInDays($booking->end_date),
                        'subtotal' => '60.00',
                        'price_breakdown' => [],
                    ]);
                }
            });
    }
}
