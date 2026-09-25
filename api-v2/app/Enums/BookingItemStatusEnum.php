<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Une ligne de rendez-vous est planifiée dès la réservation. Une option de séjour est vendue « à placer » : le pro
 * la place ensuite dans son agenda. Annulée, une ligne libère sa place dans l'agenda.
 */
enum BookingItemStatusEnum: string
{
    case TO_SCHEDULE = 'to_schedule';
    case SCHEDULED = 'scheduled';
    case DONE = 'done';
    case CANCELLED = 'cancelled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
