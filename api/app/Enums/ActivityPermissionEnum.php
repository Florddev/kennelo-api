<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityPermissionEnum: string
{
    case UPDATE_ACTIVITY = 'update_activity';
    case MANAGE_CYCLES = 'manage_cycles';
    case MANAGE_AVAILABILITIES = 'manage_availabilities';
    case MANAGE_BOOKINGS = 'manage_bookings';
    case MANAGE_MESSAGES = 'manage_messages';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
