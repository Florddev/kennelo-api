<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Politique d'annulation d'une activité. Le barème de remboursement arrive avec le lot « Séjours ».
 */
enum CancellationPolicyEnum: string
{
    case FLEXIBLE = 'flexible';
    case MODERATE = 'moderate';
    case STRICT = 'strict';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
