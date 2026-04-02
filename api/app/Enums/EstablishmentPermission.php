<?php

declare(strict_types=1);

namespace App\Enums;

enum EstablishmentPermission: string
{
    case UPDATE_ESTABLISHMENT = 'update_establishment';
    case MANAGE_CAPACITIES = 'manage_capacities';
    case MANAGE_AVAILABILITIES = 'manage_availabilities';
    case MANAGE_BOOKINGS = 'manage_bookings';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
