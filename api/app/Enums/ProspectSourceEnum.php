<?php

declare(strict_types=1);

namespace App\Enums;

enum ProspectSourceEnum: string
{
    case APIFY = 'apify';
    case MANUAL = 'manual';
    case IMPORT = 'import';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
