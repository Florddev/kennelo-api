<?php

declare(strict_types=1);

namespace App\Enums;

enum BookingStatusEnum: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';
}
