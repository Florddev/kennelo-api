<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Enums\CancellationPolicyEnum;
use App\Enums\CancelledByRoleEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\RefundReasonEnum;
use App\Models\Booking;
use App\Models\BookingRefund;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Réservation confirmée d'une place à 60 € (64,80 € payés), qui commence dans $days jours.
 */
function confirmedStay(CancellationPolicyEnum $policy, int $days): Booking
{
    return Booking::factory()->confirmed()->occupying(dogBoarding())
        ->between(today()->addDays($days)->toDateString(), today()->addDays($days + 2)->toDateString())
        ->create(['cancellation_policy' => $policy]);
}

describe('by the client', function () {
    it('cancels a request not accepted yet for free', function () {
        Notification::fake();
        $booking = Booking::factory()->create();
        $this->stripe()->fake('post', '/v1/payment_intents/*/cancel', ['object' => 'payment_intent', 'id' => 'pi_x', 'status' => 'canceled']);

        $this->withHeaders(asUser($booking->user))
            ->postJson("/api/bookings/{$booking->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.cancelled_by_role', 'client')
            ->assertJsonPath('data.payment_status', 'canceled');

        $this->stripe()->assertNotSent('post', '/v1/refunds');
        Notification::assertSentTo($booking->organization->owner, AppNotification::class);
    });

    it('refunds everything, Kennelo fee included, within the full refund period', function () {
        $booking = confirmedStay(CancellationPolicyEnum::FLEXIBLE, 3);
        stripeRefunds($this->stripe());

        $this->withHeaders(asUser($booking->user))
            ->postJson("/api/bookings/{$booking->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'refunded')
            ->assertJsonPath('data.refunded_amount', '64.80')
            ->assertJsonPath('data.total_price', '0.00');

        $refund = BookingRefund::sole();

        expect($refund->amount)->toBe('64.80')
            ->and($refund->service_fee_amount)->toBe('4.80')
            ->and($refund->reason)->toBe(RefundReasonEnum::CLIENT_CANCELLATION)
            ->and($booking->fresh()->activity_amount)->toBe('0.00');
    });

    it('refunds half of the stay, not the Kennelo fee, after the moderate deadline', function () {
        $booking = confirmedStay(CancellationPolicyEnum::MODERATE, 3);
        stripeRefunds($this->stripe());

        $this->withHeaders(asUser($booking->user))
            ->postJson("/api/bookings/{$booking->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.payment_status', 'partially_refunded')
            ->assertJsonPath('data.refunded_amount', '30.00');

        $booking->refresh();

        expect(BookingRefund::sole()->service_fee_amount)->toBe('0.00')
            ->and($booking->total_price)->toBe('34.80')
            ->and($booking->service_fee)->toBe('4.80')
            ->and($booking->platform_fee)->toBe('2.40')
            ->and($booking->activity_amount)->toBe('27.60');
    });

    it('refunds nothing after the strict deadline, the company keeping its share', function () {
        $booking = confirmedStay(CancellationPolicyEnum::STRICT, 3);

        $this->withHeaders(asUser($booking->user))
            ->postJson("/api/bookings/{$booking->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.payment_status', 'succeeded');

        $this->stripe()->assertNotSent('post', '/v1/refunds');
        expect($booking->fresh()->activity_amount)->toBe('55.20');
    });

    it('can no longer cancel a stay that has started', function () {
        $booking = Booking::factory()->confirmed()->status(BookingStatusEnum::IN_PROGRESS)
            ->between(today()->subDay()->toDateString(), today()->addDay()->toDateString())
            ->create();

        $this->withHeaders(asUser($booking->user))
            ->postJson("/api/bookings/{$booking->id}/cancel")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => __('booking.not_cancellable')]);
    });

    it('cannot cancel the booking of someone else', function () {
        $booking = Booking::factory()->create();

        $this->withHeaders(asUser($booking->organization->owner))
            ->postJson("/api/bookings/{$booking->id}/cancel")
            ->assertNotFound();
    });
});

describe('by the professional', function () {
    it('refunds the client in full, even after the deadlines', function () {
        Notification::fake();
        $booking = confirmedStay(CancellationPolicyEnum::STRICT, 1);
        stripeRefunds($this->stripe());

        $this->withHeaders(asUser($booking->organization->owner))
            ->postJson("/api/activities/{$booking->activity_id}/bookings/{$booking->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.cancelled_by_role', CancelledByRoleEnum::PRO->value)
            ->assertJsonPath('data.refunded_amount', '64.80');

        expect(BookingRefund::sole()->reason)->toBe(RefundReasonEnum::PRO_CANCELLATION)
            ->and($booking->fresh()->payment_status)->toBe(PaymentStatusEnum::REFUNDED);
        Notification::assertSentTo($booking->user, AppNotification::class);
    });

    it('declines a request instead of cancelling it', function () {
        $booking = Booking::factory()->create();

        $this->withHeaders(asUser($booking->organization->owner))
            ->postJson("/api/activities/{$booking->activity_id}/bookings/{$booking->id}/cancel")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => __('booking.reject_instead')]);
    });
});
