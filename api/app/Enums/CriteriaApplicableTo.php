<?php

declare(strict_types=1);

namespace App\Enums;

enum CriteriaApplicableTo: string
{
    case USER = 'user';
    case ESTABLISHMENT = 'establishment';
    case BOTH = 'both';

    public function matchesTarget(ReviewerType $reviewerType): bool
    {
        if ($this === self::BOTH) {
            return true;
        }

        return match ($reviewerType) {
            ReviewerType::USER => $this === self::ESTABLISHMENT,
            ReviewerType::ESTABLISHMENT => $this === self::USER,
        };
    }
}
