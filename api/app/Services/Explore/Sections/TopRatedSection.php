<?php

declare(strict_types=1);

namespace App\Services\Explore\Sections;

use App\Contracts\ExploreSection;
use App\Enums\ReviewerTypeEnum;
use Illuminate\Database\Eloquent\Builder;

class TopRatedSection implements ExploreSection
{
    public function id(): string
    {
        return 'top_rated';
    }

    public function apply(Builder $query, ?float $lat, ?float $lng): Builder
    {
        $reviewerType = ReviewerTypeEnum::USER->value;

        return $query
            ->whereRaw(
                '(SELECT COALESCE(AVG(r.overall_rating), 0) FROM reviews r INNER JOIN bookings b ON b.id = r.booking_id WHERE b.activity_id = activities.id AND r.is_published IS TRUE AND r.reviewer_type = ?) >= ?',
                [$reviewerType, 4.5]
            )
            ->whereRaw(
                '(SELECT COUNT(*) FROM reviews r INNER JOIN bookings b ON b.id = r.booking_id WHERE b.activity_id = activities.id AND r.is_published IS TRUE AND r.reviewer_type = ?) >= ?',
                [$reviewerType, 5]
            )
            ->orderByRaw('avg_rating DESC NULLS LAST')
            ->orderByDesc('review_count');
    }
}
