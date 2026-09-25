<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Occupation d'une ressource : une prestation réservée (rendez-vous ou option de séjour placée), l'absence d'une
 * personne, ou le blocage d'un équipement ou d'un espace.
 */
enum ResourceBookingKindEnum: string
{
    case BOOKING = 'booking';
    case ABSENCE = 'absence';
    case BLOCK = 'block';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Occupations posées à la main par l'équipe, sans réservation.
     *
     * @return list<self>
     */
    public static function unavailabilities(): array
    {
        return [self::ABSENCE, self::BLOCK];
    }
}
