<?php

declare(strict_types=1);

use App\Enums\BookingItemStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\CancelledByRoleEnum;
use App\Enums\PaymentKindEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PayoutStatusEnum;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Service;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;

/**
 * @return array<string, mixed>
 */
function paymentIntentEvent(BookingPayment $payment, string $status): array
{
    return ['object' => 'payment_intent', 'id' => $payment->stripe_payment_intent_id, 'status' => $status, 'latest_charge' => 'ch_webhook'];
}

it('presents the request to the team once the client confirmed the payment', function () {
    Notification::fake();
    $booking = Booking::factory()->create(['payment_status' => PaymentStatusEnum::REQUIRES_ACTION]);
    $payment = $booking->payments()->sole();

    postStripeEvent('payment_intent.amount_capturable_updated', paymentIntentEvent($payment, 'requires_capture'))->assertNoContent();

    expect($payment->fresh()->status)->toBe(PaymentStatusEnum::REQUIRES_CAPTURE)
        ->and($booking->fresh()->payment_status)->toBe(PaymentStatusEnum::REQUIRES_CAPTURE);
    Notification::assertSentTo($booking->organization->owner, AppNotification::class);
});

it('cancels a request whose payment was never authorized, and frees its units', function () {
    Notification::fake();
    $booking = Booking::factory()->create(['payment_status' => PaymentStatusEnum::REQUIRES_ACTION]);

    postStripeEvent('payment_intent.payment_failed', paymentIntentEvent($booking->payments()->sole(), 'requires_payment_method'))->assertNoContent();

    $booking->refresh();

    expect($booking->status)->toBe(BookingStatusEnum::CANCELLED)
        ->and($booking->cancelled_by_role)->toBe(CancelledByRoleEnum::PLATFORM)
        ->and($booking->payment_status)->toBe(PaymentStatusEnum::FAILED);
    Notification::assertSentTo($booking->user, AppNotification::class);
});

it('adds a supplement to the booking once the client confirmed it', function () {
    $booking = Booking::factory()->confirmed()->create();
    $payment = $booking->payments()->create([
        'kind' => PaymentKindEnum::SUPPLEMENT,
        'amount' => '21.60',
        'service_fee' => '1.60',
        'currency' => 'EUR',
        'status' => PaymentStatusEnum::REQUIRES_ACTION,
        'stripe_payment_intent_id' => 'pi_supplement',
    ]);
    $booking->items()->create([
        'service_id' => Service::factory()->for($booking->organization)->create()->id,
        'status' => BookingItemStatusEnum::TO_SCHEDULE,
        'unit_price' => '20.00',
        'subtotal' => '20.00',
        'booking_payment_id' => $payment->id,
    ]);

    postStripeEvent('payment_intent.succeeded', paymentIntentEvent($payment, 'succeeded'))->assertNoContent();
    postStripeEvent('payment_intent.succeeded', paymentIntentEvent($payment, 'succeeded'))->assertNoContent();

    $booking->refresh();

    expect($payment->fresh()->status)->toBe(PaymentStatusEnum::SUCCEEDED)
        ->and($payment->fresh()->stripe_charge_id)->toBe('ch_webhook')
        ->and($booking->total_price)->toBe('86.40')
        ->and($booking->activity_amount)->toBe('73.60');
});

it('ignores the echo of a capture already recorded, and the payments of other products', function () {
    $booking = Booking::factory()->confirmed()->create();

    postStripeEvent('payment_intent.succeeded', paymentIntentEvent($booking->payments()->sole(), 'succeeded'))->assertNoContent();
    postStripeEvent('payment_intent.succeeded', ['object' => 'payment_intent', 'id' => 'pi_subscription', 'status' => 'succeeded'])->assertNoContent();

    expect($booking->fresh()->total_price)->toBe('64.80')
        ->and($booking->operations()->count())->toBe(0);
});

it('records a payout Stripe reversed', function () {
    $booking = Booking::factory()->confirmed()->status(BookingStatusEnum::COMPLETED)->create();
    $payout = $booking->payout()->create([
        'stripe_transfer_id' => 'tr_1',
        'stripe_account_id' => 'acct_1',
        'amount' => '55.20',
        'currency' => 'EUR',
        'status' => PayoutStatusEnum::PAID,
    ]);

    postStripeEvent('transfer.reversed', ['object' => 'transfer', 'id' => 'tr_1', 'amount' => 5520])->assertNoContent();

    expect($payout->fresh()->status)->toBe(PayoutStatusEnum::CANCELED);
});
