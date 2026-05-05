<?php

declare(strict_types=1);

namespace App\Enums;

enum ReviewerType: string
{
    case USER = 'user';
    case ESTABLISHMENT = 'establishment';

    public function counterpart(): self
    {
        return match ($this) {
            self::USER => self::ESTABLISHMENT,
            self::ESTABLISHMENT => self::USER,
        };
    }
}
