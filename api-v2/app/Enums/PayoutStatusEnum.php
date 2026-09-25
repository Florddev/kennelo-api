<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Un versement est un virement Stripe vers le compte Connect de l'entreprise : il y arrive aussitôt (paid),
 * et n'en repart que s'il est annulé chez Stripe (canceled).
 */
enum PayoutStatusEnum: string
{
    case PENDING = 'pending';
    case IN_TRANSIT = 'in_transit';
    case PAID = 'paid';
    case FAILED = 'failed';
    case CANCELED = 'canceled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
