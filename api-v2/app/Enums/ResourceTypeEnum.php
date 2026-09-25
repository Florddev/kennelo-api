<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Ce qui se réserve dans l'agenda : une personne de l'équipe, un équipement (table de toilettage), un espace (salle).
 */
enum ResourceTypeEnum: string
{
    case STAFF = 'staff';
    case EQUIPMENT = 'equipment';
    case SPACE = 'space';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
