<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Jours de la semaine en puissances de deux : une valeur seule désigne un jour, leur somme un ensemble
 * de jours (masque de bits, comme activity_period_settings.closed_weekdays).
 */
enum WeekDayEnum: int
{
    case MONDAY = 1;
    case TUESDAY = 2;
    case WEDNESDAY = 4;
    case THURSDAY = 8;
    case FRIDAY = 16;
    case SATURDAY = 32;
    case SUNDAY = 64;

    public const int ALL = 127;

    public const int NONE = 0;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<self>
     */
    public static function fromMask(int $mask): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $day): bool => ($mask & $day->value) === $day->value,
        ));
    }

    /**
     * @param  array<int, self>  $days
     */
    public static function toMask(array $days): int
    {
        return array_reduce(
            $days,
            static fn (int $mask, self $day): int => $mask | $day->value,
            self::NONE,
        );
    }

    public static function contains(int $mask, self $day): bool
    {
        return ($mask & $day->value) === $day->value;
    }
}
