<?php

declare(strict_types=1);

namespace App\Enums;

enum VatRegimeEnum: string
{
    case FRANCHISE = 'franchise';
    case STANDARD = 'standard';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
