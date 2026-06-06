<?php

declare(strict_types=1);

namespace App\Enums;

enum ApiStatusEnum: string
{
    case SUCCESS = 'success';
    case ERROR = 'error';
}
