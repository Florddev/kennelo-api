<?php

declare(strict_types=1);

namespace App\Enums;

enum SubscriptionPaymentStatusEnum: string
{
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case PENDING = 'pending';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
