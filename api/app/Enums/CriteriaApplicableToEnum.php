<?php

declare(strict_types=1);

namespace App\Enums;

enum CriteriaApplicableToEnum: string
{
    case USER = 'user';
    case ACTIVITY = 'activity';
    case BOTH = 'both';

    public function matchesTarget(ReviewerTypeEnum $reviewerType): bool
    {
        if ($this === self::BOTH) {
            return true;
        }

        return match ($reviewerType) {
            ReviewerTypeEnum::USER => $this === self::ACTIVITY,
            ReviewerTypeEnum::ACTIVITY => $this === self::USER,
        };
    }
}
