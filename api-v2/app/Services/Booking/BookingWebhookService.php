<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\BookingItemStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\CancelledByRoleEnum;
use App\Enums\FinancialOperationTypeEnum;
use App\Enums\PaymentKindEnum;
use App\Enums\PaymentStatusEnum;
use App\Events\Booking\BookingCancelled;
use App\Events\Booking\BookingCreated;
use App\Events\Booking\BookingPaymentCaptured;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Services\Finance\FinancialJournalService;
use Illuminate\Support\Facades\DB;
use Stripe\PaymentIntent;

/**
 * Suites des paiements de réservation annoncées par Stripe : ce que le client confirme dans le front (3-D Secure)
 * ou ce qui échoue après coup. Un PaymentIntent qui n'est pas celui d'une réservation (abonnement) est ignoré.
 */
class BookingWebhookService
{
    /**
     * Seuls ces événements changent l'état d'un paiement de réservation ; payment_intent.created, par exemple,
     * décrit un paiement que l'API vient de créer et que le service a déjà enregistré.
     */
    public const array PAYMENT_EVENTS = [
        'payment_intent.amount_capturable_updated',
        'payment_intent.succeeded',
        'payment_intent.payment_failed',
        'payment_intent.canceled',
    ];

    public function __construct(
        private readonly BookingService $bookings,
        private readonly BookingPaymentService $payments,
        private readonly BookingAdjustmentService $adjustments,
        private readonly FinancialJournalService $journal,
    ) {}

    public function syncPaymentIntent(PaymentIntent $intent): void
    {
        $payment = BookingPayment::query()->where('stripe_payment_intent_id', $intent->id)->with('booking')->first();

        if ($payment?->booking === null) {
            return;
        }

        $booking = $payment->booking;
        $status = PaymentStatusEnum::fromStripe((string) $intent->status);

        $event = DB::transaction(function () use ($booking, $payment, $intent, $status): BookingCreated|BookingPaymentCaptured|BookingCancelled|null {
            $this->bookings->lock($booking);
            $payment->refresh();

            // Déjà traité par l'API (capture, remboursement) ou par une livraison précédente.
            if ($payment->status === $status || $payment->status === PaymentStatusEnum::SUCCEEDED) {
                return null;
            }

            return match ($status) {
                PaymentStatusEnum::REQUIRES_CAPTURE => $this->authorized($booking, $payment),
                PaymentStatusEnum::SUCCEEDED => $this->charged($booking, $payment, $intent),
                PaymentStatusEnum::FAILED, PaymentStatusEnum::CANCELED => $this->failed($booking, $payment, $status),
                default => $this->record($booking, $payment, $status),
            };
        });

        if ($event !== null) {
            event($event);
        }
    }

    /**
     * Le client a confirmé le paiement initial : la demande part au pro.
     */
    private function authorized(Booking $booking, BookingPayment $payment): ?BookingCreated
    {
        $this->record($booking, $payment, PaymentStatusEnum::REQUIRES_CAPTURE);

        return $payment->kind === PaymentKindEnum::INITIAL && $booking->status === BookingStatusEnum::PENDING
            ? new BookingCreated($booking)
            : null;
    }

    /**
     * Un complément que le client a confirmé est encaissé : il s'ajoute aux montants de la réservation.
     */
    private function charged(Booking $booking, BookingPayment $payment, PaymentIntent $intent): ?BookingPaymentCaptured
    {
        if ($payment->kind !== PaymentKindEnum::SUPPLEMENT) {
            return null;
        }

        $this->payments->markSucceeded($booking, $payment, $intent, FinancialOperationTypeEnum::CHARGE);
        $this->adjustments->applySupplement($booking, $payment);

        return new BookingPaymentCaptured($payment);
    }

    /**
     * Paiement initial jamais autorisé : la demande est annulée par Kennelo et ses places libérées. Complément
     * refusé : ses options sont retirées.
     */
    private function failed(Booking $booking, BookingPayment $payment, PaymentStatusEnum $status): ?BookingCancelled
    {
        $this->record($booking, $payment, $status);

        if ($payment->kind === PaymentKindEnum::SUPPLEMENT) {
            $payment->items()->update(['status' => BookingItemStatusEnum::CANCELLED]);
            $this->journal->record(FinancialOperationTypeEnum::CHARGE_FAILED, $booking, $payment->amount, $payment->stripe_payment_intent_id);

            return null;
        }

        if ($booking->status !== BookingStatusEnum::PENDING) {
            return null;
        }

        $booking->items()->update(['status' => BookingItemStatusEnum::CANCELLED]);
        $this->bookings->transition($booking, BookingStatusEnum::CANCELLED, [
            'cancelled_at' => now(),
            'cancelled_by_role' => CancelledByRoleEnum::PLATFORM,
        ]);

        return new BookingCancelled($booking);
    }

    private function record(Booking $booking, BookingPayment $payment, PaymentStatusEnum $status): null
    {
        $payment->forceFill(['status' => $status])->save();

        if ($payment->kind === PaymentKindEnum::INITIAL) {
            $booking->forceFill(['payment_status' => $status])->save();
        }

        return null;
    }
}
