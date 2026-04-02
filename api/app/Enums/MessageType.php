<?php

declare(strict_types=1);

namespace App\Enums;

enum MessageType: string
{
    case Text = 'text';
    case File = 'file';
    case BookingReference = 'booking_reference';
    case System = 'system';
}
