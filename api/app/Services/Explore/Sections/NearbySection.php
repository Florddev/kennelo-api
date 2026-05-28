<?php

declare(strict_types=1);

namespace App\Services\Explore\Sections;

use App\Contracts\ExploreSection;
use Illuminate\Database\Eloquent\Builder;

class NearbySection implements ExploreSection
{
    use HasHaversine;

    public function id(): string
    {
        return 'nearby';
    }

    public function apply(Builder $query, ?float $lat, ?float $lng): Builder
    {
        if ($lat === null || $lng === null || ! $this->supportsGeo()) {
            return $query
                ->orderByRaw('avg_rating DESC NULLS LAST')
                ->orderByDesc('review_count');
        }

        $query
            ->join('addresses as addr_nearby', 'addr_nearby.id', '=', 'establishments.address_id')
            ->whereNotNull('addr_nearby.latitude')
            ->whereNotNull('addr_nearby.longitude')
            ->orderBy('distance')
            ->orderByRaw('avg_rating DESC NULLS LAST');

        $this->applyDistanceSelect($query, $lat, $lng, 'addr_nearby.latitude', 'addr_nearby.longitude');

        return $query;
    }
}
