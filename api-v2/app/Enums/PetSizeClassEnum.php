<?php

declare(strict_types=1);

namespace App\Enums;

enum PetSizeClassEnum: string
{
    case SMALL = 'small';
    case MEDIUM = 'medium';
    case LARGE = 'large';
    case GIANT = 'giant';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
