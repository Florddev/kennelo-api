<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Cycle de vie d'une réservation. Chaque changement de statut passe par canTransitionTo().
 *
 *   pending ──> confirmed ──> in_progress ──> completed
 *      │            │              │
 *      ├──> rejected, expired      └──> cancelled
 *      └──> cancelled   └──> cancelled, completed
 */
enum BookingStatusEnum: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case CANCELLED = 'cancelled';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
    case COMPLETED = 'completed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Statuts qui occupent des places : ils comptent dans la capacité d'une activité.
     *
     * @return list<self>
     */
    public static function occupying(): array
    {
        return [self::PENDING, self::CONFIRMED, self::IN_PROGRESS];
    }

    public function isOccupying(): bool
    {
        return in_array($this, self::occupying(), true);
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, match ($this) {
            self::PENDING => [self::CONFIRMED, self::REJECTED, self::EXPIRED, self::CANCELLED],
            // Un séjour confirmé peut se clore sans être passé par « en cours » si la tâche de début n'est pas passée.
            self::CONFIRMED => [self::IN_PROGRESS, self::COMPLETED, self::CANCELLED],
            self::IN_PROGRESS => [self::COMPLETED, self::CANCELLED],
            self::CANCELLED, self::REJECTED, self::EXPIRED, self::COMPLETED => [],
        }, true);
    }
}
