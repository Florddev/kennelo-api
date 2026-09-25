<?php

declare(strict_types=1);

namespace App\Enums;

enum CancelledByRoleEnum: string
{
    case CLIENT = 'client';
    case PRO = 'pro';
    case PLATFORM = 'platform';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
