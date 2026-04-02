<?php

declare(strict_types=1);

namespace App\Enums;

enum SenderType: string
{
    case User = 'user';
    case Establishment = 'establishment';
    case System = 'system';
}
