<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;

enum BillingUnitEnum: string
{
    case NIGHT = 'night';
    case DAY = 'day';
    case SLOT = 'slot';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Dates facturées d'un séjour : chaque nuit du départ exclu, ou chaque jour départ compris.
     *
     * @return list<CarbonImmutable>
     */
    public function stayDates(CarbonInterface $start, CarbonInterface $end): array
    {
        $last = $this === self::NIGHT ? $end->toImmutable()->subDay() : $end->toImmutable();

        if ($last->lessThan($start)) {
            return [];
        }

        return array_map(
            fn (CarbonInterface $date): CarbonImmutable => $date->toImmutable()->startOfDay(),
            iterator_to_array(CarbonPeriod::create($start->toImmutable()->startOfDay(), $last->startOfDay())),
        );
    }
}
