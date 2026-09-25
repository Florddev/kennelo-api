<?php

declare(strict_types=1);

namespace App\Services\Pricing;

use App\Enums\WeekDayEnum;
use App\Models\Activity;
use App\Models\ActivityAvailability;
use App\Models\ActivityPeriodSetting;
use App\Models\ActivityUnitType;
use App\Models\Organization;
use App\Models\PricingPeriod;
use App\Services\Stay\UnitTypeService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Tarifs des séjours d'une activité : comment elle applique chaque période de son entreprise, sa grille,
 * et le calendrier des prix montré aux clients.
 */
class ActivityPricingService
{
    public function __construct(
        private readonly UnitTypeService $unitTypes,
    ) {}

    /**
     * Toutes les périodes de l'entreprise, chacune avec le réglage et la grille de l'activité (relation
     * activitySetting, null tant que l'activité ne l'applique pas).
     *
     * @return Collection<int, PricingPeriod>
     */
    public function periods(Activity $activity): Collection
    {
        $settings = $activity->periodSettings()->with('prices')->get()->keyBy('pricing_period_id');

        return $activity->organization()->firstOrFail()->pricingPeriods()->get()
            ->each(fn (PricingPeriod $period) => $period->setRelation('activitySetting', $settings->get($period->id)));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSetting(Activity $activity, PricingPeriod $period, array $data): ActivityPeriodSetting
    {
        $setting = $this->settingFor($activity, $period);
        $setting->fill(Arr::except($data, 'closed_weekdays'));

        if (array_key_exists('closed_weekdays', $data)) {
            $setting->closed_weekdays = WeekDayEnum::toMask(array_map(WeekDayEnum::from(...), $data['closed_weekdays'] ?? []));
        }

        $setting->save();

        // Relue d'une nouvelle instance : un PUT répond 200, même quand il crée le réglage.
        return $setting->fresh('prices') ?? $setting;
    }

    /**
     * Remplace la grille de l'activité pour cette période ; le réglage est créé avec ses valeurs par défaut s'il manque.
     *
     * @param  list<array<string, mixed>>  $lines
     */
    public function replacePrices(Activity $activity, PricingPeriod $period, array $lines): ActivityPeriodSetting
    {
        return DB::transaction(function () use ($activity, $period, $lines): ActivityPeriodSetting {
            $setting = $this->settingFor($activity, $period);

            if (! $setting->exists) {
                $setting->save();
            }

            $setting->prices()->delete();
            $setting->prices()->createMany(array_map(fn (array $line): array => [
                'activity_unit_type_id' => $line['unit_type_id'],
                'weekday' => $line['weekday'] ?? null,
                'price' => $line['price'],
                'extra_animal_price' => $line['extra_animal_price'] ?? null,
            ], $lines));

            return $setting->fresh('prices') ?? $setting;
        });
    }

    /**
     * Tarif de l'activité pour des séjours compris entre $from et $to.
     *
     * Après un retour à une offre inférieure, si le réglage soft_disable_periods est actif, seules les
     * périodes les plus anciennes dans la limite du quota s'appliquent ; les autres restent enregistrées.
     */
    public function calculator(Activity $activity, CarbonInterface $from, CarbonInterface $to): StayPriceCalculator
    {
        $settings = $activity->periodSettings()
            ->where('is_active', true)
            ->with(['pricingPeriod', 'prices'])
            ->get();

        $base = $settings->first(fn (ActivityPeriodSetting $setting): bool => (bool) $setting->pricingPeriod?->isBase());
        $applied = $this->appliedPeriodIds($activity->organization()->firstOrFail());

        $seasons = $settings
            ->filter(fn (ActivityPeriodSetting $setting): bool => in_array($setting->pricing_period_id, $applied, true))
            ->sortBy(fn (ActivityPeriodSetting $setting): int => (int) array_search($setting->pricing_period_id, $applied, true))
            ->values()
            ->all();

        $exceptions = $activity->availabilities()
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->get()
            ->mapWithKeys(fn (ActivityAvailability $availability): array => [$availability->date->toDateString() => $availability->status])
            ->all();

        return new StayPriceCalculator($base, $seasons, $exceptions);
    }

    /**
     * Calendrier des prix, du $from au $to : pour chaque date, l'ouverture, le séjour minimum si un séjour y
     * commence, et pour chaque place active son prix pour un animal et le nombre de places libres.
     *
     * @return list<array{date: string, is_open: bool, min_stay: int|null, units: list<array{unit_type_id: string, price: string|null, available: int}>}>
     */
    public function calendar(Activity $activity, CarbonInterface $from, CarbonInterface $to): array
    {
        $activity->loadMissing('profession');
        $calculator = $this->calculator($activity, $from, $to);
        $unitTypes = $this->unitTypes->forActivity($activity);
        $occupancy = $this->unitTypes->occupancy($activity, $activity->profession->billing_unit, $from, $to);
        $days = [];

        foreach (CarbonPeriod::create($from, $to) as $date) {
            $date = CarbonImmutable::parse($date->toDateString());
            $isOpen = $calculator->isOpen($date);

            $days[] = [
                'date' => $date->toDateString(),
                'is_open' => $isOpen,
                'min_stay' => $isOpen ? $calculator->minStay($date) : null,
                'units' => ! $isOpen ? [] : $unitTypes->map(fn (ActivityUnitType $unitType): array => [
                    'unit_type_id' => $unitType->id,
                    'price' => $calculator->unitPrice($unitType->id, $date)['price'] ?? null,
                    'available' => max(0, $unitType->quantity - ($occupancy[$unitType->id][$date->toDateString()] ?? 0)),
                ])->values()->all(),
            ];
        }

        return $days;
    }

    /**
     * Périodes saisonnières appliquées, dans leur ordre de création.
     *
     * @return list<string>
     */
    private function appliedPeriodIds(Organization $organization): array
    {
        $plan = $organization->effectivePlan();
        $query = $organization->pricingPeriods()->seasonal();

        if (setting('soft_disable_periods') && ! $plan->isUnlimited('max_periods')) {
            $query->limit((int) $plan->limit('max_periods'));
        }

        return $query->pluck('id')->all();
    }

    private function settingFor(Activity $activity, PricingPeriod $period): ActivityPeriodSetting
    {
        return ActivityPeriodSetting::query()
            ->where('activity_id', $activity->id)
            ->where('pricing_period_id', $period->id)
            ->first()
            ?? (new ActivityPeriodSetting)->forceFill([
                'organization_id' => $activity->organization_id,
                'activity_id' => $activity->id,
                'pricing_period_id' => $period->id,
            ]);
    }
}
