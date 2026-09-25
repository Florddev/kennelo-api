<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Revue de l'activité par l'équipe Kennelo. Une activité n'est réservable qu'une fois approuvée.
 */
enum ActivityStatusEnum: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case SUSPENDED = 'suspended';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
