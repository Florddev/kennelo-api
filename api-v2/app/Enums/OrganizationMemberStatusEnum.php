<?php

declare(strict_types=1);

namespace App\Enums;

enum OrganizationMemberStatusEnum: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case DECLINED = 'declined';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
