<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\DisputeStatusEnum;
use App\Enums\FinancialOperationTypeEnum;
use App\Enums\PayoutStatusEnum;
use App\Events\Booking\BookingDisputeClosed;
use App\Events\Booking\BookingDisputeOpened;
use App\Models\Booking;
use App\Models\BookingDispute;
use App\Models\BookingPayment;
use App\Services\Finance\FinancialJournalService;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Dispute;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class BookingDisputeService
{
    public const array EVENTS = [
        'charge.dispute.created',
        'charge.dispute.updated',
        'charge.dispute.closed',
    ];

    public function __construct(
        private readonly StripeClient $stripe,
        private readonly BookingService $bookings,
        private readonly FinancialJournalService $journal,
    ) {}

    public function sync(Dispute $dispute): void
    {
        $payment = BookingPayment::query()
            ->where(fn (Builder $query) => $query
                ->where('stripe_payment_intent_id', (string) $dispute->payment_intent)
                ->orWhere('stripe_charge_id', (string) $dispute->charge))
            ->with('booking')
            ->first();

        if ($payment?->booking === null) {
            return;
        }

        $events = DB::transaction(fn (): array => $this->record($this->bookings->lock($payment->booking), $payment, $dispute));

        foreach ($events as $event) {
            event($event);
        }
    }

    /**
     * @return list<BookingDisputeOpened|BookingDisputeClosed>
     */
    private function record(Booking $booking, BookingPayment $payment, Dispute $stripeDispute): array
    {
        $status = DisputeStatusEnum::from((string) $stripeDispute->status);
        $dispute = BookingDispute::query()->where('stripe_dispute_id', $stripeDispute->id)->first();
        $attributes = [
            'status' => $status,
            'evidence_due_by' => isset($stripeDispute->evidence_details->due_by) ? Carbon::createFromTimestamp((int) $stripeDispute->evidence_details->due_by) : null,
        ];

        if ($dispute?->status->isClosed()) {
            return [];
        }

        $events = [];

        if ($dispute === null) {
            $dispute = $booking->disputes()->create([
                ...$attributes,
                'booking_payment_id' => $payment->id,
                'stripe_dispute_id' => $stripeDispute->id,
                'amount' => Money::fromCents((int) $stripeDispute->amount),
                'currency' => mb_strtoupper((string) $stripeDispute->currency),
                'reason' => (string) $stripeDispute->reason,
            ]);
            $this->journal->record(FinancialOperationTypeEnum::DISPUTE_OPENED, $booking, $dispute->amount, $dispute->stripe_dispute_id, ['reason' => $dispute->reason]);

            if (! $status->isClosed()) {
                $events[] = new BookingDisputeOpened($dispute);
            }
        } else {
            $dispute->forceFill($attributes)->save();
        }

        if ($status->isClosed()) {
            $this->close($booking, $dispute);
            $events[] = new BookingDisputeClosed($dispute);
        }

        return $events;
    }

    private function close(Booking $booking, BookingDispute $dispute): void
    {
        $dispute->forceFill(['closed_at' => now()])->save();

        if ($dispute->status !== DisputeStatusEnum::LOST) {
            $this->journal->record(FinancialOperationTypeEnum::DISPUTE_WON, $booking, stripeReference: $dispute->stripe_dispute_id);

            return;
        }

        $this->journal->record(FinancialOperationTypeEnum::DISPUTE_LOST, $booking, $dispute->amount, $dispute->stripe_dispute_id);
        $recovered = $this->recover($booking, $dispute);

        if (bccomp($recovered, '0', 2) > 0) {
            $dispute->forceFill(['recovered_amount' => $recovered])->save();
            $this->journal->record(FinancialOperationTypeEnum::DISPUTE_RECOVERY, $booking, $recovered, $dispute->stripe_dispute_id);
        }
    }

    /**
     * @return numeric-string
     */
    private function recover(Booking $booking, BookingDispute $dispute): string
    {
        $payout = $booking->payout()->first();

        if ($payout === null) {
            $recovered = Money::min($dispute->amount, $booking->activity_amount);
            $booking->forceFill(['activity_amount' => bcsub($booking->activity_amount, $recovered, 2)])->save();

            return $recovered;
        }

        if ($payout->status !== PayoutStatusEnum::PAID) {
            return '0.00';
        }

        $alreadyRecovered = (string) $booking->disputes()->whereKeyNot($dispute->id)->sum('recovered_amount');
        $recovered = Money::min($dispute->amount, bcsub($payout->amount, Money::round($alreadyRecovered), 2));

        if (bccomp($recovered, '0', 2) <= 0) {
            return '0.00';
        }

        try {
            $this->stripe->transfers->createReversal($payout->stripe_transfer_id, [
                'amount' => Money::toCents($recovered),
                'metadata' => ['booking_id' => $booking->id, 'dispute_id' => $dispute->stripe_dispute_id],
            ], ['idempotency_key' => 'dispute_'.$dispute->stripe_dispute_id]);
        } catch (ApiErrorException $exception) {
            Log::warning('Lost dispute not recovered from the organization', ['booking_id' => $booking->id, 'dispute_id' => $dispute->stripe_dispute_id, 'error' => $exception->getMessage()]);

            return '0.00';
        }

        return $recovered;
    }
}
