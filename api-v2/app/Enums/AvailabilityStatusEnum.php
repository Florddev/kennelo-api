<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Exception aux horaires habituels sur une journée entière : fermeture, ou ouverture un jour normalement fermé.
 */
enum AvailabilityStatusEnum: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
