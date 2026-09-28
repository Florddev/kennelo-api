<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\BookingStatusEnum;
use App\Enums\FinancialOperationTypeEnum;
use App\Enums\PaymentKindEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PayoutStatusEnum;
use App\Events\Booking\BookingPaidOut;
use App\Models\Booking;
use App\Models\BookingPayout;
use App\Services\Finance\FinancialJournalService;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Stripe\Transfer;

/**
 * Versement de ce qui revient à l'entreprise, sur son compte Stripe Connect.
 *
 * Un seul versement par réservation, une fois qu'elle ne peut plus changer : le séjour est terminé (ou annulé
 * en gardant une part pour le pro) depuis payout_delay_hours. Un versement qui échoue chez Stripe est retenté
 * au passage suivant de la tâche.
 */
class BookingPayoutService
{
    public function __construct(
        private readonly StripeClient $stripe,
        private readonly FinancialJournalService $journal,
    ) {}

    public function releaseDue(): int
    {
        $released = 0;

        Booking::query()
            ->whereIn('status', [BookingStatusEnum::COMPLETED, BookingStatusEnum::CANCELLED])
            ->where('activity_amount', '>', 0)
            ->whereDate('end_date', '<=', now()->subHours((int) setting('payout_delay_hours'))->subDay())
            ->whereDoesntHave('payout')
            ->whereDoesntHave('disputes', fn (Builder $query) => $query->open())
            ->whereHas('payments', fn (Builder $query) => $query->where('status', PaymentStatusEnum::SUCCEEDED))
            ->whereHas('organization', fn (Builder $query) => $query->whereNotNull('stripe_account_id')->where('stripe_payouts_enabled', true))
            ->lazyById()
            ->each(function (Booking $booking) use (&$released): void {
                $released += (int) ($this->release($booking) !== null);
            });

        return $released;
    }

    public function release(Booking $booking): ?BookingPayout
    {
        $payout = DB::transaction(function () use ($booking): ?BookingPayout {
            $booking = Booking::query()->with(['organization', 'payments.refunds'])->lockForUpdate()->find($booking->id);

            if ($booking === null || $booking->payout()->exists() || $booking->disputes()->open()->exists() || bccomp($booking->activity_amount, '0', 2) <= 0) {
                return null;
            }

            try {
                $transfer = $this->transfer($booking);
            } catch (ApiErrorException $exception) {
                Log::warning('Booking payout failed, retried on the next run', ['booking_id' => $booking->id, 'error' => $exception->getMessage()]);

                return null;
            }

            $payout = $booking->payout()->create([
                'stripe_transfer_id' => $transfer->id,
                'stripe_account_id' => (string) $booking->organization?->stripe_account_id,
                'amount' => $booking->activity_amount,
                'currency' => $booking->currency,
                'status' => PayoutStatusEnum::PAID,
                'transferred_at' => now(),
            ]);

            $this->journal->record(FinancialOperationTypeEnum::PAYOUT, $booking, $payout->amount, $transfer->id);

            return $payout;
        });

        if ($payout !== null) {
            BookingPaidOut::dispatch($payout);
        }

        return $payout;
    }

    /**
     * Annulé chez Stripe (transfer.reversed) : l'argent est revenu sur le compte de Kennelo.
     */
    public function reverse(Transfer $transfer): void
    {
        $payout = BookingPayout::query()->where('stripe_transfer_id', $transfer->id)->with('booking')->first();

        if ($payout === null || $payout->status === PayoutStatusEnum::CANCELED || $payout->booking === null || ! $transfer->reversed) {
            return;
        }

        $payout->forceFill(['status' => PayoutStatusEnum::CANCELED])->save();
        $this->journal->record(FinancialOperationTypeEnum::PAYOUT_REVERSED, $payout->booking, $payout->amount, $transfer->id);
    }

    /**
     * Le virement puise dans le paiement initial quand il suffit (source_transaction) : les fonds partent sans
     * attendre d'être disponibles sur le compte de Kennelo.
     */
    private function transfer(Booking $booking): Transfer
    {
        $initial = $booking->payments->first(fn ($payment): bool => $payment->kind === PaymentKindEnum::INITIAL);
        $fundsFromInitial = $initial?->stripe_charge_id !== null && bccomp($initial->refundableAmount(), $booking->activity_amount, 2) >= 0;

        return $this->stripe->transfers->create(array_filter([
            'amount' => Money::toCents($booking->activity_amount),
            'currency' => mb_strtolower($booking->currency),
            'destination' => $booking->organization?->stripe_account_id,
            'transfer_group' => $booking->stripe_transfer_group,
            'source_transaction' => $fundsFromInitial ? $initial->stripe_charge_id : null,
            'metadata' => ['booking_id' => $booking->id],
        ]), ['idempotency_key' => 'payout_'.$booking->id]);
    }
}
