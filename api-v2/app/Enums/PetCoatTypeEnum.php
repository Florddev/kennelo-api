<?php

declare(strict_types=1);

namespace App\Enums;

enum PetCoatTypeEnum: string
{
    case SHORT = 'short';
    case MEDIUM = 'medium';
    case LONG = 'long';
    case CURLY = 'curly';
    case WIRE = 'wire';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
