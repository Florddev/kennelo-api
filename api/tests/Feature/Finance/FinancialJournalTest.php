<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Enums\FinancialOperationTypeEnum;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\FinancialOperation;
use App\Models\User;
use App\Services\Booking\BookingService;
use Stripe\StripeClient;
use Tests\Support\FakeStripeClient;

function fakeStripeJournal(): void
{
    app()->instance(StripeClient::class, new FakeStripeClient);
}

it('records a capture when a booking is confirmed', function () {
    fakeStripeJournal();

    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->pending()->create([
        'activity_id' => $activity->id,
        'stripe_payment_intent_id' => 'pi_journal_capture',
        'payment_status' => 'requires_capture',
    ]);

    app(BookingService::class)->confirm($booking, $manager);

    expect(
        FinancialOperation::where('booking_id', $booking->id)
            ->where('type', FinancialOperationTypeEnum::CAPTURE->value)
            ->exists()
    )->toBeTrue();
});

it('records a release and a status change when a booking is rejected', function () {
    fakeStripeJournal();

    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->pending()->create([
        'activity_id' => $activity->id,
        'stripe_payment_intent_id' => 'pi_journal_release',
        'payment_status' => 'requires_capture',
    ]);

    app(BookingService::class)->rejectByActivity($booking, $manager);

    expect(
        FinancialOperation::where('booking_id', $booking->id)
            ->where('type', FinancialOperationTypeEnum::RELEASE->value)
            ->exists()
    )->toBeTrue();
    expect(
        FinancialOperation::where('booking_id', $booking->id)
            ->where('type', FinancialOperationTypeEnum::STATUS_CHANGE->value)
            ->exists()
    )->toBeTrue();
});

// ─── endpoint ───────────────────────────────────────────────────────────────────

it('host can list the financial operations of a booking', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->create(['activity_id' => $activity->id]);
    FinancialOperation::create([
        'booking_id' => $booking->id,
        'type' => FinancialOperationTypeEnum::AUTHORIZE,
        'amount' => '100.00',
        'currency' => 'EUR',
        'stripe_reference' => 'pi_test',
    ]);

    $this->withHeaders(asUser($manager))
        ->getJson("/api/activities/{$activity->id}/bookings/{$booking->id}/operations")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('non-manager cannot list financial operations', function () {
    $manager = User::factory()->create();
    $other = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->create(['activity_id' => $activity->id]);

    $this->withHeaders(asUser($other))
        ->getJson("/api/activities/{$activity->id}/bookings/{$booking->id}/operations")
        ->assertForbidden();
});

it('cannot list financial operations of a booking from another activity', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $otherActivity = Activity::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->create([
        'activity_id' => $otherActivity->id,
        'status' => BookingStatusEnum::PENDING,
    ]);

    $this->withHeaders(asUser($manager))
        ->getJson("/api/activities/{$activity->id}/bookings/{$booking->id}/operations")
        ->assertNotFound();
});
