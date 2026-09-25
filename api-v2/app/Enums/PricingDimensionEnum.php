<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Critères qu'un métier utilise pour faire varier ses prix (professions.pricing_dimensions), en plus de l'espèce.
 */
enum PricingDimensionEnum: string
{
    case SIZE = 'size';
    case COAT = 'coat';
    case BREED = 'breed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
