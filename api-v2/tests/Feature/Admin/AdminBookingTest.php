<?php

declare(strict_types=1);

use App\Enums\AdminActionTypeEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\CancellationPolicyEnum;
use App\Enums\CancelledByRoleEnum;
use App\Enums\DisputeStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PayoutStatusEnum;
use App\Enums\RefundReasonEnum;
use App\Models\AdminAction;
use App\Models\Booking;
use App\Models\BookingDispute;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;

function stayStartingIn(int $days, CancellationPolicyEnum $policy = CancellationPolicyEnum::MODERATE): Booking
{
    return Booking::factory()->confirmed()->occupying(dogBoarding())
        ->between(today()->addDays($days)->toDateString(), today()->addDays($days + 2)->toDateString())
        ->create(['cancellation_policy' => $policy]);
}

function endedStay(): Booking
{
    return Booking::factory()->confirmed()->status(BookingStatusEnum::COMPLETED)->occupying(dogBoarding())
        ->between(today()->subDays(4)->toDateString(), today()->subDays(2)->toDateString())
        ->create();
}

function openDisputeOn(Booking $booking): BookingDispute
{
    return $booking->disputes()->create([
        'booking_payment_id' => $booking->payments()->sole()->id,
        'stripe_dispute_id' => 'dp_'.$booking->id,
        'amount' => $booking->total_price,
        'currency' => 'EUR',
        'reason' => 'fraudulent',
        'status' => DisputeStatusEnum::NEEDS_RESPONSE,
    ]);
}

function notificationOfType(NotificationTypeEnum $type): Closure
{
    return fn (AppNotification $notification): bool => $notification->toUserDatabase(new stdClass)['type'] === $type->value;
}

describe('listing', function () {
    it('lists the bookings, filtered by client, status or open dispute', function () {
        $disputed = endedStay();
        openDisputeOn($disputed);
        $other = stayStartingIn(3);
        $admin = asUser(adminUser());

        $this->withHeaders($admin)
            ->getJson('/api/admin/bookings')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->withHeaders($admin)
            ->getJson('/api/admin/bookings?search='.urlencode($other->user->email))
            ->assertJsonPath('data.*.id', [$other->id])
            ->assertJsonPath('data.0.client.email', $other->user->email);

        $this->withHeaders($admin)
            ->getJson('/api/admin/bookings?status=completed')
            ->assertJsonPath('data.*.id', [$disputed->id]);

        $this->withHeaders($admin)
            ->getJson('/api/admin/bookings?disputed=1')
            ->assertJsonPath('data.*.id', [$disputed->id])
            ->assertJsonPath('data.0.disputes.0.status', DisputeStatusEnum::NEEDS_RESPONSE->value);

        $this->withHeaders($admin)
            ->getJson('/api/admin/bookings?disputed=0')
            ->assertJsonPath('data.*.id', [$other->id]);
    });

    it('shows a booking with its payout, disputes and financial journal', function () {
        $booking = endedStay();
        openDisputeOn($booking);

        $this->withHeaders(asUser(adminUser()))
            ->getJson("/api/admin/bookings/{$booking->id}")
            ->assertOk()
            ->assertJsonPath('client.email', $booking->user->email)
            ->assertJsonPath('payout', null)
            ->assertJsonPath('disputes.0.amount', '64.80')
            ->assertJsonStructure(['payments', 'refunds', 'operations']);

        $this->withHeaders(asUser($booking->organization->owner))
            ->getJson("/api/activities/{$booking->activity_id}/bookings/{$booking->id}")
            ->assertOk()
            ->assertJsonPath('disputes.0.status', DisputeStatusEnum::NEEDS_RESPONSE->value)
            ->assertJsonMissingPath('client.email')
            ->assertJsonMissingPath('operations');
    });

    it('forbids the booking routes to anyone but an admin', function () {
        $booking = stayStartingIn(3);

        $this->withHeaders(asUser($booking->organization->owner))
            ->getJson('/api/admin/bookings')
            ->assertForbidden();

        $this->withHeaders(asUser($booking->user))
            ->postJson("/api/admin/bookings/{$booking->id}/cancel", ['reason' => 'Test'])
            ->assertForbidden();
    });
});

describe('cancellation by Kennelo', function () {
    it('refunds everything by default, warns the client and the team, and logs it', function () {
        Notification::fake();
        $admin = adminUser();
        $booking = stayStartingIn(1, CancellationPolicyEnum::STRICT);
        stripeRefunds($this->stripe());

        $this->withHeaders(asUser($admin))
            ->postJson("/api/admin/bookings/{$booking->id}/cancel", ['reason' => 'Fraude au paiement'])
            ->assertOk()
            ->assertJsonPath('status', BookingStatusEnum::CANCELLED->value)
            ->assertJsonPath('cancelled_by_role', CancelledByRoleEnum::PLATFORM->value)
            ->assertJsonPath('refunded_amount', '64.80')
            ->assertJsonPath('refunds.0.reason', RefundReasonEnum::PLATFORM_CANCELLATION->value);

        $booking->refresh();
        $action = AdminAction::query()->where('action', AdminActionTypeEnum::CANCEL_BOOKING)->sole();

        expect($booking->cancelled_by)->toBe($admin->id)
            ->and($booking->payment_status)->toBe(PaymentStatusEnum::REFUNDED)
            ->and($booking->activity_amount)->toBe('0.00')
            ->and($action->target_user_id)->toBe($booking->user_id)
            ->and($action->metadata)->toBe(['booking_id' => $booking->id, 'reason' => 'Fraude au paiement']);
        Notification::assertSentTo($booking->user, AppNotification::class, notificationOfType(NotificationTypeEnum::BOOKING_CANCELLED_BY_PLATFORM));
        Notification::assertSentTo($booking->user, AppNotification::class, notificationOfType(NotificationTypeEnum::PAYMENT_REFUNDED));
        Notification::assertSentTo($booking->organization->owner, AppNotification::class, notificationOfType(NotificationTypeEnum::TEAM_BOOKING_CANCELLED_BY_PLATFORM));
    });

    it('applies the cancellation policy when asked', function () {
        $booking = stayStartingIn(3, CancellationPolicyEnum::MODERATE);
        stripeRefunds($this->stripe());

        $this->withHeaders(asUser(adminUser()))
            ->postJson("/api/admin/bookings/{$booking->id}/cancel", ['refund' => 'policy', 'reason' => 'Annulation tardive'])
            ->assertOk()
            ->assertJsonPath('payment_status', PaymentStatusEnum::PARTIALLY_REFUNDED->value)
            ->assertJsonPath('refunded_amount', '30.00');

        expect($booking->fresh()->activity_amount)->toBe('27.60');
    });

    it('cancels a request not accepted yet without charge', function () {
        $booking = Booking::factory()->create();
        $this->stripe()->fake('post', '/v1/payment_intents/*/cancel', ['object' => 'payment_intent', 'id' => 'pi_x', 'status' => 'canceled']);

        $this->withHeaders(asUser(adminUser()))
            ->postJson("/api/admin/bookings/{$booking->id}/cancel", ['reason' => 'Annonce frauduleuse'])
            ->assertOk()
            ->assertJsonPath('status', BookingStatusEnum::CANCELLED->value)
            ->assertJsonPath('payment_status', PaymentStatusEnum::CANCELED->value);

        $this->stripe()->assertNotSent('post', '/v1/refunds');
    });

    it('refuses to cancel an ended booking, or a disputed one', function () {
        $ended = endedStay();
        $disputed = stayStartingIn(3);
        openDisputeOn($disputed);
        $admin = asUser(adminUser());

        $this->withHeaders($admin)
            ->postJson("/api/admin/bookings/{$ended->id}/cancel", ['reason' => 'Test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status' => __('booking.not_cancellable')]);

        $this->withHeaders($admin)
            ->postJson("/api/admin/bookings/{$disputed->id}/cancel", ['reason' => 'Test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['booking' => __('booking.disputed')]);

        $this->stripe()->assertNotSent('post', '/v1/refunds');
    });
});

describe('goodwill refund', function () {
    it('refunds part of the services and lowers the payout in proportion', function () {
        Notification::fake();
        $booking = endedStay();
        stripeRefunds($this->stripe());

        $this->withHeaders(asUser(adminUser()))
            ->postJson("/api/admin/bookings/{$booking->id}/refunds", ['amount' => 12, 'reason' => 'Promenade écourtée'])
            ->assertOk()
            ->assertJsonPath('refunded_amount', '12.00')
            ->assertJsonPath('payment_status', PaymentStatusEnum::PARTIALLY_REFUNDED->value)
            ->assertJsonPath('refunds.0.reason', RefundReasonEnum::GOODWILL->value);

        $booking->refresh();

        expect($booking->total_price)->toBe('52.80')
            ->and($booking->service_fee)->toBe('4.80')
            ->and($booking->platform_fee)->toBe('3.84')
            ->and($booking->activity_amount)->toBe('44.16')
            ->and(AdminAction::query()->where('action', AdminActionTypeEnum::REFUND_BOOKING)->sole()->metadata)
            ->toBe(['booking_id' => $booking->id, 'amount' => 12, 'reason' => 'Promenade écourtée']);
        $this->stripe()->assertSent('post', '/v1/refunds', fn (array $params): bool => $params['amount'] === 1200);
        Notification::assertSentTo($booking->user, AppNotification::class, notificationOfType(NotificationTypeEnum::PAYMENT_REFUNDED));
    });

    it('also refunds the Kennelo fees when asked, the organization keeping its share', function () {
        $booking = endedStay();
        stripeRefunds($this->stripe());

        $this->withHeaders(asUser(adminUser()))
            ->postJson("/api/admin/bookings/{$booking->id}/refunds", ['amount' => 0, 'service_fee' => true, 'reason' => 'Geste commercial'])
            ->assertOk()
            ->assertJsonPath('refunded_amount', '4.80')
            ->assertJsonPath('service_fee', '0.00')
            ->assertJsonPath('refunds.0.service_fee', '4.80');

        expect($booking->fresh()->activity_amount)->toBe('55.20');
    });

    it('refuses a refund above the services, after the payout, while a dispute is open, or before payment', function () {
        $booking = endedStay();
        $disputed = endedStay();
        openDisputeOn($disputed);
        $pending = Booking::factory()->create();
        $admin = asUser(adminUser());

        $this->withHeaders($admin)
            ->postJson("/api/admin/bookings/{$booking->id}/refunds", ['amount' => 61, 'reason' => 'Test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount' => __('booking.refund_too_high', ['max' => '60.00'])]);

        $booking->payout()->create([
            'stripe_transfer_id' => 'tr_1',
            'stripe_account_id' => 'acct_1',
            'amount' => $booking->activity_amount,
            'currency' => 'EUR',
            'status' => PayoutStatusEnum::PAID,
            'transferred_at' => now(),
        ]);

        $this->withHeaders($admin)
            ->postJson("/api/admin/bookings/{$booking->id}/refunds", ['amount' => 10, 'reason' => 'Test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['booking' => __('booking.not_refundable')]);

        $this->withHeaders($admin)
            ->postJson("/api/admin/bookings/{$disputed->id}/refunds", ['amount' => 10, 'reason' => 'Test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['booking' => __('booking.disputed')]);

        $this->withHeaders($admin)
            ->postJson("/api/admin/bookings/{$pending->id}/refunds", ['amount' => 10, 'reason' => 'Test'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['booking' => __('booking.not_refundable')]);

        $this->stripe()->assertNotSent('post', '/v1/refunds');
    });
});
