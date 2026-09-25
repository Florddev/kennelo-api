<?php

declare(strict_types=1);

namespace App\Services\Pricing;

use App\Models\Organization;
use App\Models\PricingPeriod;
use App\Models\User;
use App\Services\Subscription\PlanLimitService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Périodes tarifaires de l'entreprise. La période de base est créée avec l'entreprise : elle couvre toute
 * l'année et ne se supprime pas. Les autres comptent dans le quota max_periods de l'offre.
 */
class PricingPeriodService
{
    public function __construct(
        private readonly PlanLimitService $planLimits,
    ) {}

    /**
     * @return Collection<int, PricingPeriod>
     */
    public function forOrganization(Organization $organization): Collection
    {
        return $organization->pricingPeriods()->get();
    }

    public function createBase(Organization $organization, User $owner): PricingPeriod
    {
        return $organization->pricingPeriods()->create([
            'name' => __('booking.base_period', locale: $owner->preferredLocale()),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Organization $organization, array $data): PricingPeriod
    {
        return DB::transaction(function () use ($organization, $data): PricingPeriod {
            // Verrou sur l'entreprise : deux créations simultanées ne dépassent pas le quota à elles deux.
            Organization::query()->whereKey($organization->id)->lockForUpdate()->first();
            $this->planLimits->assertCanAddPeriod($organization);

            return $organization->pricingPeriods()->create($data)->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PricingPeriod $period, array $data): PricingPeriod
    {
        $period->update($data);

        return $period;
    }

    /**
     * Les réglages et les grilles des activités pour cette période disparaissent avec elle. Les réservations
     * gardent le détail de leur prix, figé à la création.
     */
    public function delete(PricingPeriod $period): void
    {
        if ($period->isBase()) {
            throw ValidationException::withMessages(['period' => __('booking.base_period_locked')]);
        }

        $period->delete();
    }
}
