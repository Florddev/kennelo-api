<?php

declare(strict_types=1);

namespace App\Enums;

enum CriteriaApplicableToEnum: string
{
    case USER = 'user';
    case ESTABLISHMENT = 'establishment';
    case BOTH = 'both';

    public function matchesTarget(ReviewerTypeEnum $reviewerType): bool
    {
        if ($this === self::BOTH) {
            return true;
        }

        return match ($reviewerType) {
            ReviewerTypeEnum::USER => $this === self::ESTABLISHMENT,
            ReviewerTypeEnum::ESTABLISHMENT => $this === self::USER,
        };
    }
}
