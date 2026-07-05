<?php

declare(strict_types=1);

namespace App\Enums;

enum ProspectContactTypeEnum: string
{
    case CALL = 'call';
    case EMAIL = 'email';
    case SMS = 'sms';
    case MEETING = 'meeting';
    case OTHER = 'other';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
