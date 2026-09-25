<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Comment un métier se réserve : un séjour occupe des places sur des dates, un rendez-vous un créneau de l'agenda.
 */
enum BookingModeEnum: string
{
    case STAY = 'stay';
    case APPOINTMENT = 'appointment';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Unités de facturation possibles : un séjour se compte en nuits ou en jours, un rendez-vous en créneaux.
     *
     * @return list<BillingUnitEnum>
     */
    public function billingUnits(): array
    {
        return match ($this) {
            self::STAY => [BillingUnitEnum::NIGHT, BillingUnitEnum::DAY],
            self::APPOINTMENT => [BillingUnitEnum::SLOT],
        };
    }
}
