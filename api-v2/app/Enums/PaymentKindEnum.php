<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Le paiement initial est autorisé à la réservation et capturé quand le pro accepte ; un complément est
 * débité aussitôt, hors session, quand le pro ajoute une option.
 */
enum PaymentKindEnum: string
{
    case INITIAL = 'initial';
    case SUPPLEMENT = 'supplement';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
