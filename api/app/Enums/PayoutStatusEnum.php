<?php

declare(strict_types=1);

namespace App\Enums;

enum PayoutStatusEnum: string
{
    case PENDING = 'pending';
    case IN_TRANSIT = 'in_transit';
    case PAID = 'paid';
    case FAILED = 'failed';
    case CANCELED = 'canceled';
}
