<?php

declare(strict_types=1);

namespace App\Http\Controllers\Explore\Sections;

use App\Contracts\ExploreSection;
use App\Enums\AvailabilityStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AvailableWeekendSection implements ExploreSection
{
    use HasHaversine;

    public function id(): string
    {
        return 'available_weekend';
    }

    public function apply(Builder $query, ?float $lat, ?float $lng): Builder
    {
        $saturday = Carbon::now()->next(Carbon::SATURDAY)->toDateString();
        $sunday = Carbon::now()->next(Carbon::SUNDAY)->toDateString();

        $query->whereHas('availabilities', function (Builder $q) use ($saturday, $sunday): void {
            $q->whereIn('date', [$saturday, $sunday])
                ->where('status', AvailabilityStatus::OPEN->value);
        });

        if ($lat !== null && $lng !== null && $this->supportsGeo()) {
            $query
                ->join('addresses as addr_weekend', 'addr_weekend.id', '=', 'establishments.address_id')
                ->whereNotNull('addr_weekend.latitude')
                ->whereNotNull('addr_weekend.longitude')
                ->orderBy('distance')
                ->orderByRaw('avg_rating DESC NULLS LAST');

            $this->applyDistanceSelect($query, $lat, $lng, 'addr_weekend.latitude', 'addr_weekend.longitude');
        } else {
            $query->orderByRaw('avg_rating DESC NULLS LAST');
        }

        return $query;
    }
}
