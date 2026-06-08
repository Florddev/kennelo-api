<?php

declare(strict_types=1);

namespace App\Enums;

enum ReviewerTypeEnum: string
{
    case USER = 'user';
    case ACTIVITY = 'activity';

    public function counterpart(): self
    {
        return match ($this) {
            self::USER => self::ACTIVITY,
            self::ACTIVITY => self::USER,
        };
    }
}
