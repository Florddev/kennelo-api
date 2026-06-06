<?php

declare(strict_types=1);

namespace App\Enums;

enum ReviewReportReasonEnum: string
{
    case INAPPROPRIATE = 'inappropriate';
    case OFFENSIVE = 'offensive';
    case FAKE = 'fake';
    case SPAM = 'spam';
    case OTHER = 'other';
}
