<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Comment une activité vend une prestation : seule, en option d'un séjour, ou les deux.
 */
enum ServiceOfferEnum: string
{
    case STANDALONE = 'standalone';
    case STAY_OPTION = 'stay_option';
    case BOTH = 'both';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isStayOption(): bool
    {
        return $this !== self::STANDALONE;
    }
}
