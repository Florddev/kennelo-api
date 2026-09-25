<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Statut d'un paiement (booking_payments) et résumé des paiements d'une réservation (bookings.payment_status).
 * REFUNDED et PARTIALLY_REFUNDED n'existent que dans ce résumé : un paiement ne change jamais de montant,
 * ses remboursements sont des lignes à part.
 */
enum PaymentStatusEnum: string
{
    case PENDING = 'pending';
    case REQUIRES_ACTION = 'requires_action';
    case REQUIRES_CAPTURE = 'requires_capture';
    case PROCESSING = 'processing';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case CANCELED = 'canceled';
    case PARTIALLY_REFUNDED = 'partially_refunded';
    case REFUNDED = 'refunded';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Statut d'un PaymentIntent Stripe.
     */
    public static function fromStripe(string $status): self
    {
        return match ($status) {
            'requires_capture' => self::REQUIRES_CAPTURE,
            'requires_action', 'requires_confirmation' => self::REQUIRES_ACTION,
            'processing' => self::PROCESSING,
            'succeeded' => self::SUCCEEDED,
            'canceled' => self::CANCELED,
            'requires_payment_method' => self::FAILED,
            default => self::PENDING,
        };
    }
}
