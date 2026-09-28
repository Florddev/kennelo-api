<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Enums\DisputeStatusEnum;
use App\Enums\FinancialOperationTypeEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\PayoutStatusEnum;
use App\Models\Booking;
use App\Models\BookingDispute;
use App\Models\BookingPayout;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;

function completedBooking(): Booking
{
    return Booking::factory()->confirmed()->status(BookingStatusEnum::COMPLETED)
        ->between(today()->subDays(4)->toDateString(), today()->subDays(2)->toDateString())
        ->create();
}

/**
 * @return array<string, mixed>
 */
function disputeEvent(Booking $booking, string $status, int $amount = 6480, string $id = 'dp_1'): array
{
    $payment = $booking->payments()->sole();

    return [
        'object' => 'dispute',
        'id' => $id,
        'amount' => $amount,
        'currency' => 'eur',
        'charge' => $payment->stripe_charge_id,
        'payment_intent' => $payment->stripe_payment_intent_id,
        'reason' => 'fraudulent',
        'status' => $status,
        'evidence_details' => ['due_by' => now()->addDays(7)->getTimestamp()],
    ];
}

function paidOut(Booking $booking): BookingPayout
{
    return $booking->payout()->create([
        'stripe_transfer_id' => 'tr_1',
        'stripe_account_id' => 'acct_1',
        'amount' => $booking->activity_amount,
        'currency' => 'EUR',
        'status' => PayoutStatusEnum::PAID,
        'transferred_at' => now(),
    ]);
}

function notified(NotificationTypeEnum $type): Closure
{
    return fn (AppNotification $notification): bool => $notification->toUserDatabase(new stdClass)['type'] === $type->value;
}

it('records a dispute, warns the admins and the team, and holds the payout', function () {
    Notification::fake();
    $admin = adminUser();
    $booking = completedBooking();

    postStripeEvent('charge.dispute.created', disputeEvent($booking, 'needs_response'))->assertNoContent();

    $dispute = BookingDispute::sole();

    expect($dispute->booking_id)->toBe($booking->id)
        ->and($dispute->amount)->toBe('64.80')
        ->and($dispute->status)->toBe(DisputeStatusEnum::NEEDS_RESPONSE)
        ->and($dispute->evidence_due_by)->not->toBeNull()
        ->and($booking->operations()->where('type', FinancialOperationTypeEnum::DISPUTE_OPENED)->count())->toBe(1);
    Notification::assertSentTo($admin, AppNotification::class, notified(NotificationTypeEnum::DISPUTE_OPENED));
    Notification::assertSentTo($booking->organization->owner, AppNotification::class, notified(NotificationTypeEnum::BOOKING_DISPUTED));

    $this->artisan('bookings:release-payouts')->assertSuccessful();

    $this->stripe()->assertNotSent('post', '/v1/transfers');
});

it('releases the payout once the dispute is won', function () {
    Notification::fake();
    $booking = completedBooking();
    $this->stripe()->fake('post', '/v1/transfers', ['object' => 'transfer', 'id' => 'tr_1']);

    postStripeEvent('charge.dispute.created', disputeEvent($booking, 'needs_response'))->assertNoContent();
    postStripeEvent('charge.dispute.updated', disputeEvent($booking, 'under_review'))->assertNoContent();
    postStripeEvent('charge.dispute.closed', disputeEvent($booking, 'won'))->assertNoContent();

    expect(BookingDispute::sole()->status)->toBe(DisputeStatusEnum::WON)
        ->and(BookingDispute::sole()->closed_at)->not->toBeNull();
    Notification::assertSentTo($booking->organization->owner, AppNotification::class, notified(NotificationTypeEnum::BOOKING_DISPUTE_WON));

    $this->artisan('bookings:release-payouts')->assertSuccessful();

    expect(BookingPayout::sole()->amount)->toBe('55.20');
});

it('takes a lost dispute from the payout not yet sent', function () {
    Notification::fake();
    $booking = completedBooking();
    $this->stripe()->fake('post', '/v1/transfers', ['object' => 'transfer', 'id' => 'tr_1']);

    postStripeEvent('charge.dispute.created', disputeEvent($booking, 'needs_response', 3000))->assertNoContent();
    postStripeEvent('charge.dispute.closed', disputeEvent($booking, 'lost', 3000))->assertNoContent();

    expect($booking->fresh()->activity_amount)->toBe('25.20')
        ->and(BookingDispute::sole()->recovered_amount)->toBe('30.00')
        ->and($booking->operations()->where('type', FinancialOperationTypeEnum::DISPUTE_RECOVERY)->sole()->amount)->toBe('30.00');
    Notification::assertSentTo($booking->organization->owner, AppNotification::class, notified(NotificationTypeEnum::BOOKING_DISPUTE_LOST));

    $this->artisan('bookings:release-payouts')->assertSuccessful();

    $this->stripe()->assertSent('post', '/v1/transfers', fn (array $params): bool => $params['amount'] === 2520);
});

it('reverses the transfer of a lost dispute already paid out, up to the payout', function () {
    $booking = completedBooking();
    paidOut($booking);
    $this->stripe()->fake('post', '/v1/transfers/tr_1/reversals', ['object' => 'transfer_reversal', 'id' => 'trr_1', 'amount' => 5520]);

    postStripeEvent('charge.dispute.closed', disputeEvent($booking, 'lost'))->assertNoContent();

    $this->stripe()->assertSent('post', '/v1/transfers/tr_1/reversals', fn (array $params): bool => $params['amount'] === 5520);
    expect(BookingDispute::sole()->recovered_amount)->toBe('55.20');

    postStripeEvent('transfer.reversed', ['object' => 'transfer', 'id' => 'tr_1', 'amount' => 5520, 'amount_reversed' => 3000, 'reversed' => false])->assertNoContent();

    expect(BookingPayout::sole()->status)->toBe(PayoutStatusEnum::PAID);
});

it('keeps the loss to Kennelo when the reversal fails', function () {
    $booking = completedBooking();
    paidOut($booking);
    $this->stripe()->fake('post', '/v1/transfers/tr_1/reversals', ['error' => ['type' => 'invalid_request_error', 'message' => 'Insufficient funds']], 400);

    postStripeEvent('charge.dispute.closed', disputeEvent($booking, 'lost'))->assertNoContent();

    expect(BookingDispute::sole()->status)->toBe(DisputeStatusEnum::LOST)
        ->and(BookingDispute::sole()->recovered_amount)->toBe('0.00')
        ->and($booking->operations()->where('type', FinancialOperationTypeEnum::DISPUTE_RECOVERY)->exists())->toBeFalse();
});

it('ignores a closed dispute updated again, and the disputes of other payments', function () {
    $booking = completedBooking();

    postStripeEvent('charge.dispute.closed', disputeEvent($booking, 'won'))->assertNoContent();
    postStripeEvent('charge.dispute.updated', disputeEvent($booking, 'needs_response'))->assertNoContent();
    postStripeEvent('charge.dispute.created', [...disputeEvent($booking, 'needs_response', id: 'dp_2'), 'charge' => 'ch_subscription', 'payment_intent' => 'pi_subscription'])->assertNoContent();

    expect(BookingDispute::sole()->status)->toBe(DisputeStatusEnum::WON);
});
