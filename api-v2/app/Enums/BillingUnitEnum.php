<?php

declare(strict_types=1);

namespace App\Enums;

enum BillingUnitEnum: string
{
    case NIGHT = 'night';
    case DAY = 'day';
    case SLOT = 'slot';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
