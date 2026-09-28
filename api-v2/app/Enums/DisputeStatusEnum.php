<?php

declare(strict_types=1);

namespace App\Enums;

enum DisputeStatusEnum: string
{
    case WARNING_NEEDS_RESPONSE = 'warning_needs_response';
    case WARNING_UNDER_REVIEW = 'warning_under_review';
    case WARNING_CLOSED = 'warning_closed';
    case NEEDS_RESPONSE = 'needs_response';
    case UNDER_REVIEW = 'under_review';
    case WON = 'won';
    case LOST = 'lost';
    case PREVENTED = 'prevented';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<self>
     */
    public static function open(): array
    {
        return array_values(array_filter(self::cases(), fn (self $status): bool => ! $status->isClosed()));
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::WARNING_CLOSED, self::WON, self::LOST, self::PREVENTED], true);
    }
}
