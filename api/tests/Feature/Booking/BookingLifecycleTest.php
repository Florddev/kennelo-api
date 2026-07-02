<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Jobs\ExpireBookingJob;
use App\Jobs\ReleaseBookingPayoutJob;
use App\Jobs\SendBookingReminderJob;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\BookingPayout;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\Booking\BookingPayoutService;
use App\Services\Booking\BookingService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Stripe\StripeClient;
use Tests\Support\FakeStripeClient;

function fakeStripeLifecycle(): void
{
    app()->instance(StripeClient::class, new FakeStripeClient);
}

function payoutReadyActivity(): Activity
{
    $manager = User::factory()->create([
        'stripe_account_id' => 'acct_payout',
        'stripe_payouts_enabled' => true,
    ]);

    return Activity::factory()->create([
        'manager_id' => $manager->id,
        'stripe_account_id' => 'acct_payout',
        'stripe_payouts_enabled' => true,
    ]);
}

// ─── expiration ─────────────────────────────────────────────────────────────────

it('dispatches an expire job for stale pending bookings', function () {
    Bus::fake();
    config(['booking.acceptance_window_hours' => 72]);

    $booking = Booking::factory()->pending()->create();
    $booking->forceFill(['created_at' => now()->subHours(80)])->save();

    expect(app(BookingService::class)->expireStalePending())->toBe(1);

    Bus::assertDispatched(ExpireBookingJob::class);
});

it('does not dispatch an expire job for recent bookings', function () {
    Bus::fake();
    config(['booking.acceptance_window_hours' => 72]);

    $booking = Booking::factory()->pending()->create();
    $booking->forceFill(['created_at' => now()->subHours(2)])->save();

    expect(app(BookingService::class)->expireStalePending())->toBe(0);

    Bus::assertNothingDispatched();
});

it('expires a booking and releases the authorization', function () {
    fakeStripeLifecycle();

    $booking = Booking::factory()->pending()->create([
        'payment_status' => 'requires_capture',
        'stripe_payment_intent_id' => 'pi_test_expire',
    ]);

    app(BookingService::class)->expireBooking($booking->id);

    expect($booking->fresh()->status)->toBe(BookingStatusEnum::EXPIRED);
    expect($booking->fresh()->payment_status->value)->toBe('canceled');

    app(BookingService::class)->expireBooking($booking->id);
    expect($booking->fresh()->status)->toBe(BookingStatusEnum::EXPIRED);
});

// ─── reminder ───────────────────────────────────────────────────────────────────

it('dispatches a reminder for mid-window pending bookings', function () {
    Bus::fake();
    config(['booking.reminder_after_hours' => 36, 'booking.acceptance_window_hours' => 72]);

    $booking = Booking::factory()->pending()->create();
    $booking->forceFill(['created_at' => now()->subHours(40)])->save();

    expect(app(BookingService::class)->remindPendingBookings())->toBe(1);

    Bus::assertDispatched(SendBookingReminderJob::class);
});

it('does not remind recent or already reminded bookings', function () {
    Bus::fake();
    config(['booking.reminder_after_hours' => 36, 'booking.acceptance_window_hours' => 72]);

    $recent = Booking::factory()->pending()->create();
    $recent->forceFill(['created_at' => now()->subHours(10)])->save();

    $reminded = Booking::factory()->pending()->create(['reminded_at' => now()]);
    $reminded->forceFill(['created_at' => now()->subHours(40)])->save();

    expect(app(BookingService::class)->remindPendingBookings())->toBe(0);

    Bus::assertNothingDispatched();
});

it('reminds the host once and marks the booking', function () {
    NotificationFacade::fake();

    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->pending()->create(['activity_id' => $activity->id]);

    app(BookingService::class)->remindBooking($booking->id);

    expect($booking->fresh()->reminded_at)->not->toBeNull();
    NotificationFacade::assertSentTo($manager, AppNotification::class);

    NotificationFacade::assertSentToTimes($manager, AppNotification::class, 1);
    app(BookingService::class)->remindBooking($booking->id);
    NotificationFacade::assertSentToTimes($manager, AppNotification::class, 1);
});

// ─── payout ─────────────────────────────────────────────────────────────────────

it('dispatches a payout job for due bookings', function () {
    Bus::fake();
    config(['booking.payout_delay_hours' => 24]);

    $activity = payoutReadyActivity();
    Booking::factory()->confirmed()->create([
        'activity_id' => $activity->id,
        'payment_status' => 'succeeded',
        'stripe_charge_id' => 'ch_payout',
        'activity_amount' => 100.00,
        'check_in_date' => now()->subDays(2)->format('Y-m-d'),
        'check_out_date' => now()->addDay()->format('Y-m-d'),
    ]);

    expect(app(BookingPayoutService::class)->releaseDuePayouts())->toBe(1);

    Bus::assertDispatched(ReleaseBookingPayoutJob::class);
});

it('does not dispatch a payout job before the delay', function () {
    Bus::fake();
    config(['booking.payout_delay_hours' => 24]);

    $activity = payoutReadyActivity();
    Booking::factory()->confirmed()->create([
        'activity_id' => $activity->id,
        'payment_status' => 'succeeded',
        'stripe_charge_id' => 'ch_payout',
        'activity_amount' => 100.00,
        'check_in_date' => now()->addDays(3)->format('Y-m-d'),
        'check_out_date' => now()->addDays(5)->format('Y-m-d'),
    ]);

    expect(app(BookingPayoutService::class)->releaseDuePayouts())->toBe(0);

    Bus::assertNothingDispatched();
});

it('creates a payout once and is idempotent', function () {
    fakeStripeLifecycle();

    $activity = payoutReadyActivity();
    $booking = Booking::factory()->confirmed()->create([
        'activity_id' => $activity->id,
        'payment_status' => 'succeeded',
        'stripe_charge_id' => 'ch_payout',
        'activity_amount' => 100.00,
        'check_in_date' => now()->subDays(2)->format('Y-m-d'),
        'check_out_date' => now()->addDay()->format('Y-m-d'),
    ]);

    $service = app(BookingPayoutService::class);

    $service->releasePayout($booking->id);
    $service->releasePayout($booking->id);

    expect(BookingPayout::where('booking_id', $booking->id)->count())->toBe(1);
});
