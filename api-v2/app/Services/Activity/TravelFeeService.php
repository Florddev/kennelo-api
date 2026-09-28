<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Models\Activity;
use App\Models\ActivityTravelFeeTier;
use App\Support\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TravelFeeService
{
    /**
     * @return Collection<int, ActivityTravelFeeTier>
     */
    public function forActivity(Activity $activity): Collection
    {
        return $activity->travelFeeTiers()->get();
    }

    /**
     * @param  list<array{up_to_km: int, fee: numeric-string}>  $tiers
     * @return Collection<int, ActivityTravelFeeTier>
     */
    public function replace(Activity $activity, array $tiers): Collection
    {
        DB::transaction(function () use ($activity, $tiers): void {
            $activity->travelFeeTiers()->delete();
            $activity->travelFeeTiers()->createMany($tiers);
        });

        return $this->forActivity($activity);
    }

    /**
     * @return numeric-string
     */
    public function feeFor(Activity $activity, float $distanceKm): string
    {
        $tiers = $this->forActivity($activity);
        $tier = $tiers->first(fn (ActivityTravelFeeTier $tier): bool => $distanceKm <= $tier->up_to_km) ?? $tiers->last();

        return $tier === null ? '0.00' : Money::round($tier->fee);
    }
}
