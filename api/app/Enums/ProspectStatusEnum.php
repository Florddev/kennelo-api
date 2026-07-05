<?php

declare(strict_types=1);

namespace App\Enums;

enum ProspectStatusEnum: string
{
    case NON_CONTACTE = 'non_contacte';
    case CONTACTE = 'contacte';
    case RELANCE = 'relance';
    case INSCRIT = 'inscrit';
    case REFUSE = 'refuse';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
