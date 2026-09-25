<?php

declare(strict_types=1);

namespace App\Services\Pricing;

use App\Enums\AvailabilityStatusEnum;
use App\Enums\WeekDayEnum;
use App\Models\ActivityPeriodPrice;
use App\Models\ActivityPeriodSetting;
use App\Services\Pricing\Exceptions\StayUnavailableException;
use App\Support\Money;
use Carbon\CarbonInterface;

/**
 * Tarif d'une activité pour un séjour, sans accès à la base : il reçoit les réglages déjà chargés
 * (ActivityPricingService::calculator()).
 *
 * - Période d'une nuit : parmi les périodes qui la contiennent, celle qui a la plus forte priorité, sinon la
 *   plus courte ; à défaut, la période de base.
 * - Prix d'une place : celui du jour de la semaine, sinon celui de « tous les jours » ; à défaut, le prix de la
 *   période de base majoré du pourcentage de la période. Chaque animal au-delà du premier dans la place ajoute
 *   son supplément, qui suit les mêmes règles.
 * - Jour fermé : une fermeture exceptionnelle, ou un jour fermé de la période ; une ouverture exceptionnelle
 *   l'emporte sur le jour fermé.
 * - Séjour minimum : celui de la période de la première nuit.
 */
final class StayPriceCalculator
{
    /**
     * @param  ActivityPeriodSetting|null  $base  réglage de la période de base, prix chargés
     * @param  list<ActivityPeriodSetting>  $seasons  réglages actifs des autres périodes, période et prix chargés, dans l'ordre de création
     * @param  array<string, AvailabilityStatusEnum>  $exceptions  fermetures et ouvertures exceptionnelles, par date (Y-m-d)
     */
    public function __construct(
        private readonly ?ActivityPeriodSetting $base,
        private readonly array $seasons,
        private readonly array $exceptions = [],
    ) {}

    public function settingFor(CarbonInterface $date): ?ActivityPeriodSetting
    {
        $candidates = array_values(array_filter(
            $this->seasons,
            fn (ActivityPeriodSetting $setting): bool => $setting->pricingPeriod?->contains($date) ?? false,
        ));

        // Tri stable : à priorité et durée égales, la période créée la première l'emporte.
        usort($candidates, fn (ActivityPeriodSetting $a, ActivityPeriodSetting $b): int => [
            $b->pricingPeriod->priority ?? PHP_INT_MIN,
            $a->pricingPeriod?->lengthInDays(),
        ] <=> [
            $a->pricingPeriod->priority ?? PHP_INT_MIN,
            $b->pricingPeriod?->lengthInDays(),
        ]);

        return $candidates[0] ?? $this->base;
    }

    public function isOpen(CarbonInterface $date): bool
    {
        $exception = $this->exceptions[$date->toDateString()] ?? null;

        if ($exception !== null) {
            return $exception === AvailabilityStatusEnum::OPEN;
        }

        $setting = $this->settingFor($date);

        return $setting !== null && ! $setting->isClosedOn(WeekDayEnum::fromDate($date));
    }

    /**
     * Prix d'une place pour un animal, ce jour-là ; null si la place n'y a pas de prix.
     *
     * @return array{price: numeric-string, extra_animal_price: numeric-string|null}|null
     */
    public function unitPrice(string $unitTypeId, CarbonInterface $date): ?array
    {
        $setting = $this->settingFor($date);

        if ($setting === null) {
            return null;
        }

        $day = WeekDayEnum::fromDate($date);
        $price = $this->gridPrice($setting, $unitTypeId, $day);

        if ($price !== null || $this->base === null || $setting === $this->base) {
            return $price;
        }

        $basePrice = $this->gridPrice($this->base, $unitTypeId, $day);

        if ($basePrice === null) {
            return null;
        }

        $modifier = $setting->price_modifier_percent ?? '0';

        return [
            'price' => Money::adjust($basePrice['price'], $modifier),
            'extra_animal_price' => $basePrice['extra_animal_price'] === null ? null : Money::adjust($basePrice['extra_animal_price'], $modifier),
        ];
    }

    /**
     * Prix d'une place occupée par $animals animaux pendant tout le séjour, nuit par nuit.
     *
     * @param  list<CarbonInterface>  $dates  nuits (ou jours) du séjour
     * @return array{subtotal: numeric-string, breakdown: list<array{date: string, pricing_period_id: string, price: numeric-string, extra_animals_price: numeric-string}>}
     *
     * @throws StayUnavailableException
     */
    public function priceUnit(string $unitTypeId, int $animals, array $dates): array
    {
        $breakdown = [];

        foreach ($dates as $date) {
            if (! $this->isOpen($date)) {
                throw StayUnavailableException::closed($date);
            }

            $setting = $this->settingFor($date);
            $price = $this->unitPrice($unitTypeId, $date);

            if ($setting === null || $price === null) {
                throw StayUnavailableException::unpriced($date);
            }

            $breakdown[] = [
                'date' => $date->toDateString(),
                'pricing_period_id' => $setting->pricing_period_id,
                'price' => $price['price'],
                'extra_animals_price' => Money::multiply($price['extra_animal_price'] ?? '0', (string) max(0, $animals - 1)),
            ];
        }

        return [
            'subtotal' => Money::sum(...array_merge(...array_map(
                fn (array $night): array => [$night['price'], $night['extra_animals_price']],
                $breakdown,
            ))),
            'breakdown' => $breakdown,
        ];
    }

    public function minStay(CarbonInterface $firstDate): int
    {
        return $this->settingFor($firstDate)->min_stay ?? 1;
    }

    /**
     * Ligne du jour de la semaine, sinon de « tous les jours ». Un prix du samedi sans supplément reprend
     * celui de tous les jours : le supplément par animal varie rarement selon le jour.
     *
     * @return array{price: numeric-string, extra_animal_price: numeric-string|null}|null
     */
    private function gridPrice(ActivityPeriodSetting $setting, string $unitTypeId, WeekDayEnum $day): ?array
    {
        $rows = $setting->prices->where('activity_unit_type_id', $unitTypeId);
        $everyDay = $rows->first(fn (ActivityPeriodPrice $row): bool => $row->weekday === null);
        $row = $rows->first(fn (ActivityPeriodPrice $row): bool => $row->weekday === $day) ?? $everyDay;

        if ($row === null) {
            return null;
        }

        return ['price' => $row->price, 'extra_animal_price' => $row->extra_animal_price ?? $everyDay?->extra_animal_price];
    }
}
