<?php

declare(strict_types=1);

namespace App\Enums;

enum OrganizationStatusEnum: string
{
    case PENDING = 'pending';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
    case SUSPENDED = 'suspended';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
