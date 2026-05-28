<?php

declare(strict_types=1);

namespace App\Services\Explore\Sections;

use App\Contracts\ExploreSection;
use Illuminate\Database\Eloquent\Builder;

class VerifiedProsSection implements ExploreSection
{
    use HasHaversine;

    public function id(): string
    {
        return 'verified_pros';
    }

    public function apply(Builder $query, ?float $lat, ?float $lng): Builder
    {
        $query
            ->whereNotNull('establishments.siret')
            ->whereHas('manager', fn (Builder $q) => $q->where('is_id_verified', true));

        if ($lat !== null && $lng !== null && $this->supportsGeo()) {
            $query
                ->join('addresses as addr_pros', 'addr_pros.id', '=', 'establishments.address_id')
                ->whereNotNull('addr_pros.latitude')
                ->whereNotNull('addr_pros.longitude')
                ->orderBy('distance')
                ->orderByRaw('avg_rating DESC NULLS LAST');

            $this->applyDistanceSelect($query, $lat, $lng, 'addr_pros.latitude', 'addr_pros.longitude');
        } else {
            $query->orderByRaw('avg_rating DESC NULLS LAST');
        }

        return $query;
    }
}
