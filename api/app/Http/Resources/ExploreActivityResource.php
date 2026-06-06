<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Activity;
use Illuminate\Http\Request;

class ExploreActivityResource extends ActivityResource
{
    public function toArray(Request $request): array
    {
        $base = parent::toArray($request);

        /** @var Activity $model */
        $model = $this->resource;

        $avgRating = $model->getAttribute('avg_rating');
        $base['rating'] = $avgRating !== null ? round((float) $avgRating, 1) : null;
        $base['review_count'] = (int) ($model->getAttribute('review_count') ?? 0);
        $distance = $model->getAttribute('distance');
        $base['distance'] = $distance !== null ? round((float) $distance, 1) : null;

        return $base;
    }
}
