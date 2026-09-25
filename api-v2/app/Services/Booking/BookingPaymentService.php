<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\FinancialOperationTypeEnum;
use App\Enums\PaymentKindEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\RefundReasonEnum;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\BookingRefund;
use App\Models\User;
use App\Services\Finance\FinancialJournalService;
use App\Services\Stripe\StripeCustomerService;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\CardException;
use Stripe\Exception\InvalidRequestException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

/**
 * Les paiements d'une réservation chez Stripe. Le paiement initial est autorisé à la réservation, capturé quand
 * le pro accepte ; la carte est gardée pour les compléments, débités hors session. Un montant payé ne change
 * jamais : on ajoute un paiement ou on rembourse.
 */
class BookingPaymentService
{
    public function __construct(
        private readonly StripeClient $stripe,
        private readonly StripeCustomerService $customers,
        private readonly FinancialJournalService $journal,
    ) {}

    /**
     * Autorise le paiement initial. Si la banque exige 3-D Secure, le client le confirme dans le front avec le
     * client_secret ; la demande n'est présentée au pro qu'une fois l'autorisation obtenue.
     *
     * @throws ValidationException carte refusée
     */
    public function authorize(User $client, BookingQuote $quote, string $bookingId, string $paymentMethodId): PaymentIntent
    {
        try {
            return $this->stripe->paymentIntents->create([
                'amount' => Money::toCents($quote->totalPrice),
                'currency' => mb_strtolower($quote->currency),
                'customer' => $this->customers->getOrCreateCustomer($client),
                'payment_method' => $paymentMethodId,
                'payment_method_types' => ['card'],
                'confirm' => true,
                'capture_method' => 'manual',
                'setup_future_usage' => 'off_session',
                'transfer_group' => 'booking_'.$bookingId,
                'metadata' => ['booking_id' => $bookingId, 'kind' => PaymentKindEnum::INITIAL->value],
            ], ['idempotency_key' => 'booking_'.$bookingId]);
        } catch (CardException|InvalidRequestException $exception) {
            Log::info('Booking payment refused', ['booking_id' => $bookingId, 'error' => $exception->getMessage()]);

            throw ValidationException::withMessages(['payment_method_id' => __('booking.payment_declined')]);
        }
    }

    public function recordInitial(Booking $booking, PaymentIntent $intent): BookingPayment
    {
        $payment = $booking->payments()->create([
            'kind' => PaymentKindEnum::INITIAL,
            'amount' => $booking->total_price,
            'currency' => $booking->currency,
            'status' => PaymentStatusEnum::fromStripe((string) $intent->status),
            'stripe_payment_intent_id' => $intent->id,
        ]);

        $this->journal->record(FinancialOperationTypeEnum::AUTHORIZE, $booking, $payment->amount, $intent->id);

        return $payment;
    }

    /**
     * Capture un paiement autorisé. Un échec est enregistré et laissé à l'appelant : l'exception annulerait
     * aussi l'enregistrement de l'échec.
     */
    public function capture(Booking $booking, BookingPayment $payment): bool
    {
        try {
            $intent = $this->stripe->paymentIntents->capture($payment->stripe_payment_intent_id);
        } catch (CardException|InvalidRequestException $exception) {
            $payment->forceFill(['status' => PaymentStatusEnum::FAILED])->save();
            $this->journal->record(FinancialOperationTypeEnum::CAPTURE_FAILED, $booking, $payment->amount, $payment->stripe_payment_intent_id, [
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        $this->markSucceeded($booking, $payment, $intent, FinancialOperationTypeEnum::CAPTURE);

        return true;
    }

    /**
     * Libère une autorisation qui ne sera pas capturée. Une autorisation déjà expirée chez Stripe est ignorée.
     */
    public function release(Booking $booking, BookingPayment $payment): void
    {
        try {
            $this->stripe->paymentIntents->cancel($payment->stripe_payment_intent_id);
        } catch (InvalidRequestException $exception) {
            Log::warning('Booking authorization already released', ['payment_id' => $payment->id, 'error' => $exception->getMessage()]);
        }

        $payment->forceFill(['status' => PaymentStatusEnum::CANCELED])->save();
        $this->journal->record(FinancialOperationTypeEnum::RELEASE, $booking, $payment->amount, $payment->stripe_payment_intent_id);
    }

    /**
     * Annule une autorisation qui n'a pas pu être rattachée à une réservation.
     */
    public function cancelIntent(string $paymentIntentId): void
    {
        $this->stripe->paymentIntents->cancel($paymentIntentId);
    }

    /**
     * Rembourse $amount, dont $serviceFee de frais Kennelo, en partant du paiement le plus récent (ou sur le seul
     * paiement donné).
     *
     * @param  numeric-string  $amount
     * @param  numeric-string  $serviceFee
     * @return Collection<int, BookingRefund>
     */
    public function refund(Booking $booking, string $amount, string $serviceFee, RefundReasonEnum $reason, ?User $actor, ?BookingPayment $from = null): Collection
    {
        $payments = $from !== null
            ? collect([$from->load('refunds')])
            : $booking->payments()->where('status', PaymentStatusEnum::SUCCEEDED)->with('refunds')->get()->reverse();
        $refunds = collect();

        foreach ($payments as $payment) {
            if (bccomp($amount, '0', 2) <= 0) {
                break;
            }

            $part = Money::min($amount, $payment->refundableAmount());
            $feePart = Money::min($serviceFee, $part);

            if (bccomp($part, '0', 2) <= 0) {
                continue;
            }

            $refundId = (string) Str::uuid();
            $stripeRefund = $this->stripe->refunds->create([
                'payment_intent' => $payment->stripe_payment_intent_id,
                'amount' => Money::toCents($part),
                'metadata' => ['booking_id' => $booking->id, 'reason' => $reason->value],
            ], ['idempotency_key' => 'refund_'.$refundId]);

            $refund = new BookingRefund([
                'amount' => $part,
                'service_fee_amount' => $feePart,
                'reason' => $reason,
                'stripe_refund_id' => $stripeRefund->id,
                'refunded_at' => $stripeRefund->status === 'succeeded' ? now() : null,
                'created_by' => $actor?->id,
            ]);
            $refund->id = $refundId;
            $payment->refunds()->save($refund);

            $this->journal->record(FinancialOperationTypeEnum::REFUND, $booking, $part, $stripeRefund->id, ['reason' => $reason->value]);

            $refunds->push($refund);
            $amount = bcsub($amount, $part, 2);
            $serviceFee = bcsub($serviceFee, $feePart, 2);
        }

        return $refunds;
    }

    /**
     * Débite un complément hors session, avec la carte du paiement initial. Si la banque exige que le client
     * s'authentifie, le paiement reste en attente de son action.
     *
     * @param  numeric-string  $amount
     *
     * @throws ValidationException carte refusée
     */
    public function chargeSupplement(Booking $booking, string $amount): BookingPayment
    {
        $initial = $booking->payments()->where('kind', PaymentKindEnum::INITIAL)->firstOrFail();
        $paymentMethod = $this->stripe->paymentIntents->retrieve($initial->stripe_payment_intent_id)->payment_method;
        $paymentId = (string) Str::uuid();

        try {
            $intent = $this->stripe->paymentIntents->create([
                'amount' => Money::toCents($amount),
                'currency' => mb_strtolower($booking->currency),
                'customer' => $booking->user?->stripe_customer_id,
                'payment_method' => is_string($paymentMethod) ? $paymentMethod : $paymentMethod?->id,
                'payment_method_types' => ['card'],
                'off_session' => true,
                'confirm' => true,
                'transfer_group' => $booking->stripe_transfer_group,
                'metadata' => ['booking_id' => $booking->id, 'kind' => PaymentKindEnum::SUPPLEMENT->value],
            ], ['idempotency_key' => 'payment_'.$paymentId]);
            $status = PaymentStatusEnum::fromStripe((string) $intent->status);
        } catch (CardException $exception) {
            $intent = $exception->getError()?->payment_intent;

            if ($exception->getStripeCode() !== 'authentication_required' || ! $intent instanceof PaymentIntent) {
                throw ValidationException::withMessages(['payment' => __('booking.supplement_declined')]);
            }

            $status = PaymentStatusEnum::REQUIRES_ACTION;
        }

        $payment = new BookingPayment([
            'kind' => PaymentKindEnum::SUPPLEMENT,
            'amount' => $amount,
            'currency' => $booking->currency,
            'status' => $status,
            'stripe_payment_intent_id' => $intent->id,
        ]);
        $payment->id = $paymentId;
        $booking->payments()->save($payment);

        if ($status === PaymentStatusEnum::SUCCEEDED) {
            $this->markSucceeded($booking, $payment, $intent, FinancialOperationTypeEnum::CHARGE);
        }

        return $payment;
    }

    /**
     * Secret du paiement, pour que le client le confirme dans le front (3-D Secure).
     */
    public function clientSecret(BookingPayment $payment): string
    {
        return (string) $this->stripe->paymentIntents->retrieve($payment->stripe_payment_intent_id)->client_secret;
    }

    public function markSucceeded(Booking $booking, BookingPayment $payment, PaymentIntent $intent, FinancialOperationTypeEnum $operation): void
    {
        $chargeId = is_string($intent->latest_charge) ? $intent->latest_charge : $intent->latest_charge?->id;

        $payment->forceFill([
            'status' => PaymentStatusEnum::SUCCEEDED,
            'stripe_charge_id' => $chargeId ?? $payment->stripe_charge_id,
            'paid_at' => now(),
        ])->save();

        $this->journal->record($operation, $booking, $payment->amount, $chargeId ?? $intent->id);
    }
}
