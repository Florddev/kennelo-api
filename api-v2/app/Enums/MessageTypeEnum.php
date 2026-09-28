<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Nature d'un message, déduite à l'envoi : du texte, des pièces jointes (avec ou sans texte), un message à propos
 * d'une réservation, ou un message système posté sur un événement de réservation.
 */
enum MessageTypeEnum: string
{
    case TEXT = 'text';
    case FILE = 'file';
    case BOOKING_REFERENCE = 'booking_reference';
    case SYSTEM = 'system';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
