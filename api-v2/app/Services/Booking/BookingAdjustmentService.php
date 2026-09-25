<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\BookingItemStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\PaymentKindEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\RefundReasonEnum;
use App\Events\Booking\BookingPaymentActionRequired;
use App\Events\Booking\BookingPaymentCaptured;
use App\Events\Booking\BookingRefunded;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingPayment;
use App\Models\BookingUnit;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ajustements d'une réservation acceptée, par le pro.
 *
 * - Ajouter une option : elle est débitée aussitôt, hors session, avec la carte du paiement initial et les frais
 *   Kennelo en vigueur. Si la banque exige que le client s'authentifie, il est prévenu et confirme le paiement.
 * - Retirer une option : elle est remboursée, avec sa part des frais Kennelo, sur le paiement qui l'a couverte.
 *
 * Les montants de la réservation ne suivent que l'argent réellement encaissé ou rendu.
 */
class BookingAdjustmentService
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly QuoteService $quotes,
        private readonly BookingPaymentService $payments,
    ) {}

    /**
     * @param  array{service_id: string, pet_id: string, quantity?: int}  $data
     */
    public function addItem(Booking $booking, array $data): BookingItem
    {
        [$item, $payment] = DB::transaction(function () use ($booking, $data): array {
            $this->bookings->lock($booking);
            $this->assertAdjustable($booking);

            $pet = $booking->pets()->findOrFail($data['pet_id']);
            $option = $this->quotes->priceOption($booking->activity()->firstOrFail(), $data['service_id'], $pet, (int) ($data['quantity'] ?? 1));

            $item = $booking->items()->create([
                'service_id' => $option['service']->id,
                'pet_id' => $pet->id,
                'status' => BookingItemStatusEnum::TO_SCHEDULE,
                'quantity' => $option['quantity'],
                'unit_price' => $option['unit_price'],
                'subtotal' => $option['subtotal'],
                'duration_minutes' => $option['duration_minutes'],
            ]);

            // Une option incluse dans le séjour ne se paie pas.
            if (bccomp($item->subtotal, '0', 2) === 0) {
                return [$item, null];
            }

            $serviceFee = Money::multiply($item->subtotal, (string) setting('user_service_fee_rate'));
            $payment = $this->payments->chargeSupplement($booking, Money::sum($item->subtotal, $serviceFee));
            $item->forceFill(['booking_payment_id' => $payment->id])->save();

            if ($payment->status === PaymentStatusEnum::SUCCEEDED) {
                $this->applySupplement($booking, $payment);
            }

            return [$item, $payment];
        });

        match ($payment?->status) {
            PaymentStatusEnum::SUCCEEDED => BookingPaymentCaptured::dispatch($payment),
            PaymentStatusEnum::REQUIRES_ACTION => BookingPaymentActionRequired::dispatch($payment),
            default => null,
        };

        return $item->load(['service', 'pet']);
    }

    /**
     * Ajoute aux montants de la réservation un complément encaissé : ses options, les frais Kennelo qu'il porte,
     * et la commission de l'offre en vigueur.
     */
    public function applySupplement(Booking $booking, BookingPayment $payment): void
    {
        $itemsAmount = Money::sum('0', ...$payment->items()->pluck('subtotal')->all());
        $platformFee = Money::multiply($itemsAmount, $booking->organization()->firstOrFail()->effectivePlan()->commissionRate());

        $booking->forceFill([
            'total_price' => Money::sum($booking->total_price, $payment->amount),
            'service_fee' => Money::sum($booking->service_fee, bcsub($payment->amount, $itemsAmount, 2)),
            'platform_fee' => Money::sum($booking->platform_fee, $platformFee),
            'activity_amount' => Money::sum($booking->activity_amount, bcsub($itemsAmount, $platformFee, 2)),
        ])->save();
    }

    public function removeItem(Booking $booking, BookingItem $item, User $actor): Booking
    {
        $refunded = DB::transaction(function () use ($booking, $item, $actor): string {
            $this->bookings->lock($booking);
            $this->assertAdjustable($booking);

            if (! in_array($item->status, [BookingItemStatusEnum::TO_SCHEDULE, BookingItemStatusEnum::SCHEDULED], true)) {
                throw ValidationException::withMessages(['item' => __('booking.item_not_removable')]);
            }

            $payment = $item->payment;
            $refund = '0.00';

            if ($payment?->status === PaymentStatusEnum::REQUIRES_ACTION) {
                // Jamais débité : l'autorisation en attente est abandonnée.
                $this->payments->release($booking, $payment);
            } elseif ($payment?->status === PaymentStatusEnum::SUCCEEDED && bccomp($item->subtotal, '0', 2) > 0) {
                $refund = $this->refundItem($booking, $item, $payment, $actor);
            }

            $item->forceFill(['status' => BookingItemStatusEnum::CANCELLED])->save();

            return $refund;
        });

        if (bccomp($refunded, '0', 2) > 0) {
            BookingRefunded::dispatch($booking, $refunded);
        }

        return $booking->load(BookingService::RELATIONS);
    }

    /**
     * @return numeric-string montant remboursé
     */
    private function refundItem(Booking $booking, BookingItem $item, BookingPayment $payment, User $actor): string
    {
        $serviceFee = $this->serviceFeeOf($item, $payment, $booking);
        $refund = Money::sum($item->subtotal, $serviceFee);
        $platformFee = Money::prorate($booking->platform_fee, $item->subtotal, $booking->itemsAmount());

        $this->payments->refund($booking, $refund, $serviceFee, RefundReasonEnum::ADJUSTMENT, $actor, $payment);

        $booking->forceFill([
            'total_price' => bcsub($booking->total_price, $refund, 2),
            'service_fee' => bcsub($booking->service_fee, $serviceFee, 2),
            'platform_fee' => bcsub($booking->platform_fee, $platformFee, 2),
            'activity_amount' => bcsub($booking->activity_amount, bcsub($item->subtotal, $platformFee, 2), 2),
            'payment_status' => PaymentStatusEnum::PARTIALLY_REFUNDED,
        ])->save();

        return $refund;
    }

    /**
     * Frais Kennelo payés pour une option : la part de l'option dans les frais du paiement qui l'a couverte.
     *
     * @return numeric-string
     */
    private function serviceFeeOf(BookingItem $item, BookingPayment $payment, Booking $booking): string
    {
        $covered = Money::sum('0', ...$payment->items()->pluck('subtotal')->all());

        // Le paiement initial couvre aussi les places et les frais de déplacement.
        if ($payment->kind === PaymentKindEnum::INITIAL) {
            $covered = Money::sum($covered, $booking->travel_fee, ...$booking->units()->get()->map(fn (BookingUnit $unit): string => $unit->subtotal)->all());
        }

        return Money::prorate(bcsub($payment->amount, $covered, 2), $item->subtotal, $covered);
    }

    private function assertAdjustable(Booking $booking): void
    {
        if (! in_array($booking->status, [BookingStatusEnum::CONFIRMED, BookingStatusEnum::IN_PROGRESS], true)) {
            throw ValidationException::withMessages(['status' => __('booking.not_adjustable')]);
        }
    }
}
