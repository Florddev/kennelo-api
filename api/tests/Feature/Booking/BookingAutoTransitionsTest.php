<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\BookingPayout;
use App\Models\User;
use App\Services\Booking\BookingPayoutService;
use App\Services\Booking\BookingService;
use Stripe\StripeClient;
use Tests\Support\FakeStripeClient;

function transitionsPayoutActivity(): Activity
{
    $manager = User::factory()->create([
        'stripe_account_id' => 'acct_transitions',
        'stripe_payouts_enabled' => true,
    ]);

    return Activity::factory()->create([
        'manager_id' => $manager->id,
        'stripe_account_id' => 'acct_transitions',
        'stripe_payouts_enabled' => true,
    ]);
}

it('moves confirmed stays to in progress once the check-in day is over', function (): void {
    $booking = Booking::factory()->confirmed()->create([
        'user_id' => User::factory(),
        'activity_id' => Activity::factory(),
        'check_in_date' => now()->subDay(),
        'check_out_date' => now()->addDays(2),
    ]);

    $count = app(BookingService::class)->startInProgressStays();

    expect($count)->toBe(1)
        ->and($booking->refresh()->status)->toBe(BookingStatusEnum::IN_PROGRESS);
});

it('keeps a stay confirmed during the check-in day so it stays cancellable', function (): void {
    $booking = Booking::factory()->confirmed()->create([
        'user_id' => User::factory(),
        'activity_id' => Activity::factory(),
        'check_in_date' => now(),
        'check_out_date' => now()->addDays(3),
    ]);

    app(BookingService::class)->startInProgressStays();

    expect($booking->refresh()->status)->toBe(BookingStatusEnum::CONFIRMED);
});

it('does not start stays before check-in', function (): void {
    $booking = Booking::factory()->confirmed()->create([
        'user_id' => User::factory(),
        'activity_id' => Activity::factory(),
        'check_in_date' => now()->addDay(),
        'check_out_date' => now()->addDays(3),
    ]);

    app(BookingService::class)->startInProgressStays();

    expect($booking->refresh()->status)->toBe(BookingStatusEnum::CONFIRMED);
});

it('completes stays only after the check-out day has passed', function (): void {
    $finished = Booking::factory()->create([
        'user_id' => User::factory(),
        'activity_id' => Activity::factory(),
        'status' => BookingStatusEnum::IN_PROGRESS,
        'check_in_date' => now()->subDays(5),
        'check_out_date' => now()->subDay(),
    ]);
    $checkoutToday = Booking::factory()->create([
        'user_id' => User::factory(),
        'activity_id' => Activity::factory(),
        'status' => BookingStatusEnum::IN_PROGRESS,
        'check_in_date' => now()->subDays(3),
        'check_out_date' => now(),
    ]);

    $count = app(BookingService::class)->completeFinishedStays();

    expect($count)->toBe(1)
        ->and($finished->refresh()->status)->toBe(BookingStatusEnum::COMPLETED)
        ->and($checkoutToday->refresh()->status)->toBe(BookingStatusEnum::IN_PROGRESS);
});

it('does not resurrect a booking cancelled between listing and processing', function (): void {
    $booking = Booking::factory()->create([
        'user_id' => User::factory(),
        'activity_id' => Activity::factory(),
        'status' => BookingStatusEnum::CANCELLED,
        'check_in_date' => now()->subDays(5),
        'check_out_date' => now()->subDay(),
    ]);

    $count = app(BookingService::class)->completeFinishedStays();

    expect($count)->toBe(0)
        ->and($booking->refresh()->status)->toBe(BookingStatusEnum::CANCELLED);
});

it('actually creates the payout for a booking that auto-completed', function (): void {
    app()->instance(StripeClient::class, new FakeStripeClient);
    config(['booking.payout_delay_hours' => 24]);

    $activity = transitionsPayoutActivity();
    $booking = Booking::factory()->completed()->create([
        'user_id' => User::factory(),
        'activity_id' => $activity->id,
        'payment_status' => PaymentStatusEnum::SUCCEEDED,
        'stripe_charge_id' => 'ch_transitions',
        'activity_amount' => 86,
        'check_in_date' => now()->subDays(5),
        'check_out_date' => now()->subDay(),
    ]);

    app(BookingPayoutService::class)->releasePayout($booking->id);

    expect(BookingPayout::where('booking_id', $booking->id)->exists())->toBeTrue();
});

it('still creates the payout for an in-progress booking', function (): void {
    app()->instance(StripeClient::class, new FakeStripeClient);

    $activity = transitionsPayoutActivity();
    $booking = Booking::factory()->create([
        'user_id' => User::factory(),
        'activity_id' => $activity->id,
        'status' => BookingStatusEnum::IN_PROGRESS,
        'payment_status' => PaymentStatusEnum::SUCCEEDED,
        'stripe_charge_id' => 'ch_transitions_2',
        'activity_amount' => 50,
        'check_in_date' => now()->subDays(2),
        'check_out_date' => now()->addDay(),
    ]);

    app(BookingPayoutService::class)->releasePayout($booking->id);

    expect(BookingPayout::where('booking_id', $booking->id)->exists())->toBeTrue();
});
