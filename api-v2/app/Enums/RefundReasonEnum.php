<?php

declare(strict_types=1);

namespace App\Enums;

enum RefundReasonEnum: string
{
    case CLIENT_CANCELLATION = 'client_cancellation';
    case PRO_CANCELLATION = 'pro_cancellation';
    case ADJUSTMENT = 'adjustment';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
