<?php

declare(strict_types=1);

namespace App\Enums;

enum MessageTypeEnum: string
{
    case TEXT = 'text';
    case FILE = 'file';
    case BOOKING_REFERENCE = 'booking_reference';
    case SYSTEM = 'system';
}
