<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Sens d'un avis : le client note l'activité (user), ou l'équipe de l'activité note le client (activity).
 */
enum ReviewerTypeEnum: string
{
    case USER = 'user';
    case ACTIVITY = 'activity';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function counterpart(): self
    {
        return match ($this) {
            self::USER => self::ACTIVITY,
            self::ACTIVITY => self::USER,
        };
    }
}
