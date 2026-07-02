<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\BookingPayout;
use App\Models\User;
use App\Services\Booking\BookingPayoutService;
use App\Services\Booking\BookingService;
use Stripe\StripeClient;
use Tests\Support\FakeStripeClient;

function fakeStripeLifecycle(): void
{
    app()->instance(StripeClient::class, new FakeStripeClient);
}

// ─── expiration ─────────────────────────────────────────────────────────────────

it('expires pending bookings past the acceptance window', function () {
    fakeStripeLifecycle();
    config(['booking.acceptance_window_hours' => 72]);

    $booking = Booking::factory()->pending()->create([
        'payment_status' => 'requires_capture',
        'stripe_payment_intent_id' => 'pi_test_expire',
    ]);
    $booking->forceFill(['created_at' => now()->subHours(80)])->save();

    $count = app(BookingService::class)->expireStalePending();

    expect($count)->toBe(1);
    expect($booking->fresh()->status)->toBe(BookingStatusEnum::EXPIRED);
    expect($booking->fresh()->payment_status->value)->toBe('canceled');
});

it('does not expire recent pending bookings', function () {
    fakeStripeLifecycle();
    config(['booking.acceptance_window_hours' => 72]);

    $booking = Booking::factory()->pending()->create();
    $booking->forceFill(['created_at' => now()->subHours(2)])->save();

    expect(app(BookingService::class)->expireStalePending())->toBe(0);
    expect($booking->fresh()->status)->toBe(BookingStatusEnum::PENDING);
});

// ─── payout ─────────────────────────────────────────────────────────────────────

it('releases payout 24h after arrival and is idempotent', function () {
    fakeStripeLifecycle();
    config(['booking.payout_delay_hours' => 24]);

    $manager = User::factory()->create([
        'stripe_account_id' => 'acct_payout',
        'stripe_payouts_enabled' => true,
    ]);
    $activity = Activity::factory()->create([
        'manager_id' => $manager->id,
        'stripe_account_id' => 'acct_payout',
        'stripe_payouts_enabled' => true,
    ]);
    $booking = Booking::factory()->confirmed()->create([
        'activity_id' => $activity->id,
        'payment_status' => 'succeeded',
        'stripe_charge_id' => 'ch_payout',
        'activity_amount' => 100.00,
        'check_in_date' => now()->subDays(2)->format('Y-m-d'),
        'check_out_date' => now()->addDay()->format('Y-m-d'),
    ]);

    $service = app(BookingPayoutService::class);

    expect($service->releaseDuePayouts())->toBe(1);
    expect(BookingPayout::where('booking_id', $booking->id)->exists())->toBeTrue();

    expect($service->releaseDuePayouts())->toBe(0);
    expect(BookingPayout::where('booking_id', $booking->id)->count())->toBe(1);
});

it('does not release payout before the delay', function () {
    fakeStripeLifecycle();
    config(['booking.payout_delay_hours' => 24]);

    $manager = User::factory()->create([
        'stripe_account_id' => 'acct_payout',
        'stripe_payouts_enabled' => true,
    ]);
    $activity = Activity::factory()->create([
        'manager_id' => $manager->id,
        'stripe_account_id' => 'acct_payout',
        'stripe_payouts_enabled' => true,
    ]);
    Booking::factory()->confirmed()->create([
        'activity_id' => $activity->id,
        'payment_status' => 'succeeded',
        'stripe_charge_id' => 'ch_payout',
        'activity_amount' => 100.00,
        'check_in_date' => now()->addDays(3)->format('Y-m-d'),
        'check_out_date' => now()->addDays(5)->format('Y-m-d'),
    ]);

    expect(app(BookingPayoutService::class)->releaseDuePayouts())->toBe(0);
});
