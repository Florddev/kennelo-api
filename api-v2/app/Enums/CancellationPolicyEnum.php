<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * Politique d'annulation d'une activité, figée sur chaque réservation. Elle fixe la part remboursée au client
 * qui annule un séjour confirmé, selon le temps restant avant son début :
 * - souple : tout jusqu'à 24 h avant, rien ensuite ;
 * - modérée : tout jusqu'à 5 jours avant, la moitié ensuite ;
 * - stricte : la moitié jusqu'à 7 jours avant, rien ensuite.
 */
enum CancellationPolicyEnum: string
{
    case FLEXIBLE = 'flexible';
    case MODERATE = 'moderate';
    case STRICT = 'strict';

    public const string FULL_REFUND = '1.00';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return numeric-string part remboursée, de 0.00 à 1.00
     */
    public function refundRate(CarbonInterface $cancelledAt, CarbonInterface $startsAt): string
    {
        $hoursBefore = $cancelledAt->diffInHours($startsAt, false);

        return match ($this) {
            self::FLEXIBLE => $hoursBefore >= 24 ? self::FULL_REFUND : '0.00',
            self::MODERATE => $hoursBefore >= 5 * 24 ? self::FULL_REFUND : '0.50',
            self::STRICT => $hoursBefore >= 7 * 24 ? '0.50' : '0.00',
        };
    }
}
