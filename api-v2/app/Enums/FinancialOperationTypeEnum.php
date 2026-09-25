<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Journal financier d'une réservation : chaque mouvement d'argent et chaque changement de statut y laisse une ligne.
 */
enum FinancialOperationTypeEnum: string
{
    case AUTHORIZE = 'authorize';
    case CAPTURE = 'capture';
    case CAPTURE_FAILED = 'capture_failed';
    case CHARGE = 'charge';
    case CHARGE_FAILED = 'charge_failed';
    case RELEASE = 'release';
    case REFUND = 'refund';
    case PAYOUT = 'payout';
    case PAYOUT_REVERSED = 'payout_reversed';
    case STATUS_CHANGE = 'status_change';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
