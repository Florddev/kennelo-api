<?php

declare(strict_types=1);

namespace App\Enums;

enum CollaboratorStatusEnum: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REFUSED = 'refused';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
