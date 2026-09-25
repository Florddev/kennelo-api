<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentStatusEnum: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
