<?php

declare(strict_types=1);

namespace App\Enums;

enum ReviewReportStatusEnum: string
{
    case PENDING = 'pending';
    case REVIEWED = 'reviewed';
    case REJECTED = 'rejected';
    case REMOVED = 'removed';
}
