<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Enums\FinancialOperationTypeEnum;
use App\Enums\OrganizationRoleEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PayoutStatusEnum;
use App\Models\Booking;
use App\Models\BookingPayout;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;

describe('answer of the team', function () {
    it('accepts a request and captures the payment', function () {
        Notification::fake();
        $booking = Booking::factory()->occupying(dogBoarding())->create();
        $this->stripe()->fake('post', '/v1/payment_intents/*/capture', ['object' => 'payment_intent', 'id' => 'pi_x', 'status' => 'succeeded', 'latest_charge' => 'ch_initial']);

        $this->withHeaders(asUser($booking->organization->owner))
            ->postJson("/api/activities/{$booking->activity_id}/bookings/{$booking->id}/confirm")
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.payment_status', 'succeeded')
            ->assertJsonPath('data.payments.0.status', 'succeeded');

        expect($booking->payments()->sole()->stripe_charge_id)->toBe('ch_initial')
            ->and($booking->operations()->pluck('type')->all())->toBe([FinancialOperationTypeEnum::CAPTURE, FinancialOperationTypeEnum::STATUS_CHANGE]);
        Notification::assertSentTo($booking->user, AppNotification::class);
    });

    it('keeps the request pending when the capture fails', function () {
        $booking = Booking::factory()->create();
        $this->stripe()->fake('post', '/v1/payment_intents/*/capture', ['error' => ['type' => 'card_error', 'code' => 'expired_card', 'message' => 'Expired']], 402);

        $this->withHeaders(asUser($booking->organization->owner))
            ->postJson("/api/activities/{$booking->activity_id}/bookings/{$booking->id}/confirm")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payment' => __('booking.capture_failed')]);

        expect($booking->fresh()->status)->toBe(BookingStatusEnum::PENDING)
            ->and($booking->fresh()->payment_status)->toBe(PaymentStatusEnum::FAILED);
    });

    it('declines a request and releases the payment', function () {
        Notification::fake();
        $booking = Booking::factory()->create();
        $this->stripe()->fake('post', '/v1/payment_intents/*/cancel', ['object' => 'payment_intent', 'id' => 'pi_x', 'status' => 'canceled']);

        $this->withHeaders(asUser($booking->organization->owner))
            ->postJson("/api/activities/{$booking->activity_id}/bookings/{$booking->id}/reject")
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.payment_status', 'canceled');

        Notification::assertSentTo($booking->user, AppNotification::class);
    });

    it('refuses to answer twice', function () {
        $booking = Booking::factory()->confirmed()->create();

        $this->withHeaders(asUser($booking->organization->owner))
            ->postJson("/api/activities/{$booking->activity_id}/bookings/{$booking->id}/reject")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    });

    it('lets an employee see the bookings of the activity, not answer them', function () {
        $booking = Booking::factory()->create();
        $employee = memberOf($booking->organization, OrganizationRoleEnum::EMPLOYEE, $booking->activity_id);

        $this->withHeaders(asUser($employee))
            ->getJson("/api/activities/{$booking->activity_id}/bookings/{$booking->id}")
            ->assertOk();

        $this->postJson("/api/activities/{$booking->activity_id}/bookings/{$booking->id}/confirm")
            ->assertForbidden();
    });

    it('hides the booking from an outsider and from another activity', function () {
        $booking = Booking::factory()->create();
        $other = Booking::factory()->create();

        $this->withHeaders(asUser(User::factory()->create()))
            ->postJson("/api/activities/{$booking->activity_id}/bookings/{$booking->id}/confirm")
            ->assertNotFound();

        $this->withHeaders(asUser($booking->organization->owner))
            ->getJson("/api/activities/{$booking->activity_id}/bookings/{$other->id}")
            ->assertNotFound();
    });
});

describe('scheduled tasks', function () {
    it('expires the requests left without an answer, or starting today', function () {
        Notification::fake();
        $this->stripe()->fake('post', '/v1/payment_intents/*/cancel', ['object' => 'payment_intent', 'id' => 'pi_x', 'status' => 'canceled']);
        $late = Booking::factory()->create(['created_at' => now()->subHours(73)]);
        $starting = Booking::factory()->between(today()->toDateString(), today()->addDays(2)->toDateString())->create();
        $recent = Booking::factory()->create(['created_at' => now()->subHours(10)]);

        $this->artisan('bookings:expire')->assertSuccessful();

        expect($late->fresh()->status)->toBe(BookingStatusEnum::EXPIRED)
            ->and($late->fresh()->payment_status)->toBe(PaymentStatusEnum::CANCELED)
            ->and($starting->fresh()->status)->toBe(BookingStatusEnum::EXPIRED)
            ->and($recent->fresh()->status)->toBe(BookingStatusEnum::PENDING);
        Notification::assertSentTo($late->user, AppNotification::class);
    });

    it('reminds the team once of a request waiting for its answer', function () {
        Notification::fake();
        $booking = Booking::factory()->create(['created_at' => now()->subHours(40)]);

        $this->artisan('bookings:remind')->assertSuccessful();
        $this->artisan('bookings:remind')->assertSuccessful();

        Notification::assertSentToTimes($booking->organization->owner, AppNotification::class, 1);
    });

    it('starts the stays of the day and completes the finished ones', function () {
        Notification::fake();
        $starting = Booking::factory()->confirmed()->between(today()->toDateString(), today()->addDays(2)->toDateString())->create();
        $finished = Booking::factory()->confirmed()->status(BookingStatusEnum::IN_PROGRESS)->between(today()->subDays(3)->toDateString(), today()->subDay()->toDateString())->create();

        $this->artisan('bookings:advance')->assertSuccessful();

        expect($starting->fresh()->status)->toBe(BookingStatusEnum::IN_PROGRESS)
            ->and($finished->fresh()->status)->toBe(BookingStatusEnum::COMPLETED);
        Notification::assertSentTo($finished->user, AppNotification::class);
    });
});

describe('payouts', function () {
    it('pays the company once the stay is over, from the initial payment', function () {
        Notification::fake();
        $booking = Booking::factory()->confirmed()->status(BookingStatusEnum::COMPLETED)
            ->between(today()->subDays(4)->toDateString(), today()->subDays(2)->toDateString())
            ->create();
        $this->stripe()->fake('post', '/v1/transfers', ['object' => 'transfer', 'id' => 'tr_1']);

        $this->artisan('bookings:release-payouts')->assertSuccessful();

        $payout = BookingPayout::sole();

        expect($payout->booking_id)->toBe($booking->id)
            ->and($payout->amount)->toBe('55.20')
            ->and($payout->status)->toBe(PayoutStatusEnum::PAID)
            ->and($payout->stripe_account_id)->toBe($booking->organization->stripe_account_id);
        $this->stripe()->assertSent('post', '/v1/transfers', fn (array $params): bool => $params['amount'] === 5520
            && $params['destination'] === $booking->organization->stripe_account_id
            && $params['source_transaction'] === $booking->payments()->sole()->stripe_charge_id);
        Notification::assertSentTo($booking->organization->owner, AppNotification::class);

        $this->artisan('bookings:release-payouts')->assertSuccessful();

        expect(BookingPayout::count())->toBe(1);
    });

    it('waits for the delay after the departure', function () {
        Booking::factory()->confirmed()->status(BookingStatusEnum::COMPLETED)
            ->between(today()->subDays(2)->toDateString(), today()->subDay()->toDateString())
            ->create();

        $this->artisan('bookings:release-payouts')->assertSuccessful();

        $this->stripe()->assertNotSent('post', '/v1/transfers');
    });

    it('retries a payout Stripe refused on the next run', function () {
        Booking::factory()->confirmed()->status(BookingStatusEnum::COMPLETED)
            ->between(today()->subDays(4)->toDateString(), today()->subDays(2)->toDateString())
            ->create();
        $this->stripe()->fake('post', '/v1/transfers', ['error' => ['type' => 'invalid_request_error', 'message' => 'Insufficient funds']], 400);

        $this->artisan('bookings:release-payouts')->assertSuccessful();

        expect(BookingPayout::count())->toBe(0);
    });
});
