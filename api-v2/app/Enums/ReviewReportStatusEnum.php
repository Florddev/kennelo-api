<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Suite donnée à un signalement : en attente, examiné sans suite (reviewed), rejeté, ou avis retiré (removed).
 */
enum ReviewReportStatusEnum: string
{
    case PENDING = 'pending';
    case REVIEWED = 'reviewed';
    case REJECTED = 'rejected';
    case REMOVED = 'removed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Décisions qu'un admin peut prendre.
     *
     * @return list<string>
     */
    public static function decisions(): array
    {
        return [self::REVIEWED->value, self::REJECTED->value, self::REMOVED->value];
    }
}
