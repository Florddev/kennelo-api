<?php

declare(strict_types=1);

namespace App\Enums;

enum SenderTypeEnum: string
{
    case USER = 'user';
    case ACTIVITY = 'activity';
    case SYSTEM = 'system';
}
