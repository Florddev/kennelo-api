<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Où la prestation a lieu : chez le pro, chez le client, ou à distance.
 */
enum LocationModeEnum: string
{
    case AT_PRO = 'at_pro';
    case AT_CLIENT = 'at_client';
    case REMOTE = 'remote';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
