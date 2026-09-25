<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\BookingItemStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\CancellationPolicyEnum;
use App\Enums\CancelledByRoleEnum;
use App\Enums\PaymentKindEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\RefundReasonEnum;
use App\Events\Booking\BookingCancelled;
use App\Events\Booking\BookingRefunded;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Annulation d'une réservation par le client ou par le pro.
 *
 * - Demande pas encore acceptée : le client l'annule sans frais, l'autorisation est libérée. Le pro, lui, la refuse.
 * - Réservation confirmée, annulée par le client avant le début du séjour : remboursement selon la politique
 *   figée à la réservation (CancellationPolicyEnum). Les frais Kennelo ne sont rendus que si tout est remboursé.
 * - Réservation confirmée ou en cours, annulée par le pro : tout est remboursé, frais Kennelo compris.
 *
 * Après un remboursement partiel, la commission et le versement de l'entreprise diminuent dans la même proportion.
 */
class CancellationService
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly BookingPaymentService $payments,
    ) {}

    public function cancelByClient(Booking $booking, User $client): Booking
    {
        $refunded = DB::transaction(function () use ($booking, $client): string {
            $this->bookings->lock($booking);

            if ($booking->status === BookingStatusEnum::PENDING) {
                $this->bookings->releaseInitial($booking);
                $this->markCancelled($booking, $client, CancelledByRoleEnum::CLIENT);

                return '0.00';
            }

            if ($booking->status !== BookingStatusEnum::CONFIRMED || now()->greaterThanOrEqualTo($booking->startsAt())) {
                throw ValidationException::withMessages(['status' => __('booking.not_cancellable')]);
            }

            $rate = $booking->cancellation_policy->refundRate(now(), $booking->startsAt());
            $refunded = $this->refundShare($booking, $rate, RefundReasonEnum::CLIENT_CANCELLATION, $client);
            $this->markCancelled($booking, $client, CancelledByRoleEnum::CLIENT);

            return $refunded;
        });

        return $this->announce($booking, $refunded);
    }

    public function cancelByPro(Booking $booking, User $actor): Booking
    {
        $refunded = DB::transaction(function () use ($booking, $actor): string {
            $this->bookings->lock($booking);

            if ($booking->status === BookingStatusEnum::PENDING) {
                throw ValidationException::withMessages(['status' => __('booking.reject_instead')]);
            }

            if (! $booking->status->canTransitionTo(BookingStatusEnum::CANCELLED)) {
                throw ValidationException::withMessages(['status' => __('booking.not_cancellable')]);
            }

            $refunded = $this->refundShare($booking, CancellationPolicyEnum::FULL_REFUND, RefundReasonEnum::PRO_CANCELLATION, $actor);
            $this->markCancelled($booking, $actor, CancelledByRoleEnum::PRO);

            return $refunded;
        });

        return $this->announce($booking, $refunded);
    }

    /**
     * Rembourse la part $rate des prestations, et les frais Kennelo si tout est remboursé, puis recalcule les
     * montants nets de la réservation.
     *
     * @param  numeric-string  $rate
     * @return numeric-string montant remboursé
     */
    private function refundShare(Booking $booking, string $rate, RefundReasonEnum $reason, User $actor): string
    {
        $itemsAmount = $booking->itemsAmount();
        $itemsRefund = Money::multiply($itemsAmount, $rate);
        $feeRefund = bccomp($rate, CancellationPolicyEnum::FULL_REFUND, 2) === 0 ? $booking->service_fee : '0.00';
        $refund = Money::sum($itemsRefund, $feeRefund);

        if (bccomp($refund, '0', 2) > 0) {
            $this->payments->refund($booking, $refund, $feeRefund, $reason, $actor);
        }

        $kept = bcsub($itemsAmount, $itemsRefund, 2);
        $serviceFee = bcsub($booking->service_fee, $feeRefund, 2);
        $platformFee = Money::prorate($booking->platform_fee, $kept, $itemsAmount);

        $booking->forceFill([
            'service_fee' => $serviceFee,
            'total_price' => Money::sum($kept, $serviceFee),
            'platform_fee' => $platformFee,
            'activity_amount' => bcsub($kept, $platformFee, 2),
            'payment_status' => match (true) {
                bccomp($refund, '0', 2) === 0 => $booking->payment_status,
                bccomp($kept, '0', 2) === 0 && bccomp($serviceFee, '0', 2) === 0 => PaymentStatusEnum::REFUNDED,
                default => PaymentStatusEnum::PARTIALLY_REFUNDED,
            },
        ]);

        return $refund;
    }

    private function markCancelled(Booking $booking, User $actor, CancelledByRoleEnum $role): void
    {
        // Un complément en attente de l'authentification du client ne sera jamais débité.
        $booking->payments()
            ->where('kind', PaymentKindEnum::SUPPLEMENT)
            ->where('status', PaymentStatusEnum::REQUIRES_ACTION)
            ->get()
            ->each(fn (BookingPayment $payment) => $this->payments->release($booking, $payment));

        $booking->items()->whereNot('status', BookingItemStatusEnum::DONE)->update(['status' => BookingItemStatusEnum::CANCELLED]);

        $this->bookings->transition($booking, BookingStatusEnum::CANCELLED, [
            'cancelled_at' => now(),
            'cancelled_by' => $actor->id,
            'cancelled_by_role' => $role,
        ]);
    }

    /**
     * @param  numeric-string  $refunded
     */
    private function announce(Booking $booking, string $refunded): Booking
    {
        BookingCancelled::dispatch($booking);

        if (bccomp($refunded, '0', 2) > 0) {
            BookingRefunded::dispatch($booking, $refunded);
        }

        return $booking->load(BookingService::RELATIONS);
    }
}
