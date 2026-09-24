<?php

declare(strict_types=1);

namespace App\Enums;

enum SettingGroupEnum: string
{
    case FEES = 'fees';
    case BOOKING = 'booking';
    case STRIPE = 'stripe';
    case NOTIFICATIONS = 'notifications';
    case DOWNGRADE = 'downgrade';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
