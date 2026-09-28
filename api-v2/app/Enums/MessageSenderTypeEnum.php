<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Côté d'une conversation d'où part un message : le client (user), l'équipe de l'activité (activity), ou Kennelo
 * pour un message système.
 */
enum MessageSenderTypeEnum: string
{
    case USER = 'user';
    case ACTIVITY = 'activity';
    case SYSTEM = 'system';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
