<?php

declare(strict_types=1);

use App\Enums\AvailabilityStatusEnum;
use App\Enums\WeekDayEnum;
use App\Models\ActivityPeriodPrice;
use App\Models\ActivityPeriodSetting;
use App\Models\PricingPeriod;
use App\Services\Pricing\Exceptions\StayUnavailableException;
use App\Services\Pricing\StayPriceCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\TestCase;

// Le framework est démarré pour les casts de dates des modèles, sans base de données.
uses(TestCase::class);

const BOX = 'box';
const SUITE = 'suite';

/**
 * @param  array<string, mixed>  $attributes
 */
function pricingPeriod(string $id, ?string $start = null, ?string $end = null, array $attributes = []): PricingPeriod
{
    return (new PricingPeriod)->forceFill(['id' => $id, 'name' => $id, 'start_date' => $start, 'end_date' => $end, 'is_recurring' => false, 'priority' => null, ...$attributes]);
}

/**
 * @param  list<array{0: string, 1: string, 2?: WeekDayEnum|null, 3?: string|null}>  $prices  [place, prix, jour, supplément par animal]
 * @param  array<string, mixed>  $attributes
 */
function periodSetting(PricingPeriod $period, array $prices, array $attributes = []): ActivityPeriodSetting
{
    $setting = (new ActivityPeriodSetting)->forceFill([
        'pricing_period_id' => $period->id,
        'is_active' => true,
        'min_stay' => null,
        'price_modifier_percent' => null,
        'closed_weekdays' => WeekDayEnum::NONE,
        ...$attributes,
    ]);

    return $setting->setRelation('pricingPeriod', $period)->setRelation('prices', collect(array_map(
        fn (array $price): ActivityPeriodPrice => new ActivityPeriodPrice([
            'activity_unit_type_id' => $price[0],
            'price' => $price[1],
            'weekday' => $price[2] ?? null,
            'extra_animal_price' => $price[3] ?? null,
        ]),
        $prices,
    )));
}

function night(string $date): CarbonImmutable
{
    return CarbonImmutable::parse($date);
}

/**
 * Tarif de base : le box à 30 €, 35 € le samedi, 10 € par chien supplémentaire.
 */
function baseSetting(array $attributes = []): ActivityPeriodSetting
{
    return periodSetting(pricingPeriod('base'), [
        [BOX, '30.00', null, '10.00'],
        [BOX, '35.00', WeekDayEnum::SATURDAY],
    ], $attributes);
}

describe('period of a night', function () {
    it('falls back on the base period', function () {
        $calculator = new StayPriceCalculator(baseSetting(), []);

        expect($calculator->settingFor(night('2026-03-10'))->pricing_period_id)->toBe('base');
    });

    it('prefers the shortest period that contains the night', function () {
        $summer = periodSetting(pricingPeriod('summer', '2026-07-01', '2026-08-31'), [[BOX, '40.00']]);
        $august = periodSetting(pricingPeriod('august', '2026-08-01', '2026-08-31'), [[BOX, '45.00']]);
        $calculator = new StayPriceCalculator(baseSetting(), [$summer, $august]);

        expect($calculator->settingFor(night('2026-08-15'))->pricing_period_id)->toBe('august')
            ->and($calculator->settingFor(night('2026-07-15'))->pricing_period_id)->toBe('summer');
    });

    it('lets a priority beat a shorter period', function () {
        $summer = periodSetting(pricingPeriod('summer', '2026-07-01', '2026-08-31', ['priority' => 1]), [[BOX, '40.00']]);
        $august = periodSetting(pricingPeriod('august', '2026-08-01', '2026-08-31'), [[BOX, '45.00']]);
        $calculator = new StayPriceCalculator(baseSetting(), [$summer, $august]);

        expect($calculator->settingFor(night('2026-08-15'))->pricing_period_id)->toBe('summer');
    });

    it('repeats a recurring period every year, across the 31st of December', function () {
        $holidays = periodSetting(pricingPeriod('holidays', '2025-12-20', '2026-01-05', ['is_recurring' => true]), [[BOX, '50.00']]);
        $calculator = new StayPriceCalculator(baseSetting(), [$holidays]);

        expect($calculator->settingFor(night('2030-12-31'))->pricing_period_id)->toBe('holidays')
            ->and($calculator->settingFor(night('2031-01-03'))->pricing_period_id)->toBe('holidays')
            ->and($calculator->settingFor(night('2031-01-06'))->pricing_period_id)->toBe('base');
    });
});

describe('price of a night', function () {
    it('takes the price of the weekday, else the price of every day', function () {
        $calculator = new StayPriceCalculator(baseSetting(), []);

        expect($calculator->unitPrice(BOX, night('2026-03-14'))['price'])->toBe('35.00')
            ->and($calculator->unitPrice(BOX, night('2026-03-13'))['price'])->toBe('30.00');
    });

    it('raises the base price by the modifier of a period without its own price', function () {
        $summer = periodSetting(pricingPeriod('summer', '2026-07-01', '2026-08-31'), [], ['price_modifier_percent' => '15.00']);
        $calculator = new StayPriceCalculator(baseSetting(), [$summer]);

        expect($calculator->unitPrice(BOX, night('2026-07-07')))->toBe(['price' => '34.50', 'extra_animal_price' => '11.50'])
            ->and($calculator->unitPrice(BOX, night('2026-07-11')))->toBe(['price' => '40.25', 'extra_animal_price' => '11.50']);
    });

    it('has no price for a unit missing from the grid', function () {
        $calculator = new StayPriceCalculator(baseSetting(), []);

        expect($calculator->unitPrice(SUITE, night('2026-03-10')))->toBeNull();
    });
});

describe('price of a stay', function () {
    it('adds up the nights and the extra animals sharing the unit', function () {
        $calculator = new StayPriceCalculator(baseSetting(), []);

        $quote = $calculator->priceUnit(BOX, 2, [night('2026-03-13'), night('2026-03-14')]);

        expect($quote['subtotal'])->toBe('85.00')
            ->and($quote['breakdown'])->toBe([
                ['date' => '2026-03-13', 'pricing_period_id' => 'base', 'price' => '30.00', 'extra_animals_price' => '10.00'],
                ['date' => '2026-03-14', 'pricing_period_id' => 'base', 'price' => '35.00', 'extra_animals_price' => '10.00'],
            ]);
    });

    it('charges nothing for extra animals when the grid sets no supplement', function () {
        $calculator = new StayPriceCalculator(periodSetting(pricingPeriod('base'), [[BOX, '30.00']]), []);

        expect($calculator->priceUnit(BOX, 2, [night('2026-03-14')])['subtotal'])->toBe('30.00');
    });

    it('refuses a night on a closed weekday, unless the activity opens it exceptionally', function () {
        $closedOnSunday = baseSetting(['closed_weekdays' => WeekDayEnum::SUNDAY->value]);

        expect(fn () => (new StayPriceCalculator($closedOnSunday, []))->priceUnit(BOX, 1, [night('2026-03-15')]))
            ->toThrow(StayUnavailableException::class, __('booking.closed_on', ['date' => '2026-03-15']));

        $opened = new StayPriceCalculator($closedOnSunday, [], ['2026-03-15' => AvailabilityStatusEnum::OPEN]);

        expect($opened->priceUnit(BOX, 1, [night('2026-03-15')])['subtotal'])->toBe('30.00');
    });

    it('refuses a night the activity closed exceptionally', function () {
        $calculator = new StayPriceCalculator(baseSetting(), [], ['2026-03-10' => AvailabilityStatusEnum::CLOSED]);

        expect(fn () => $calculator->priceUnit(BOX, 1, [night('2026-03-09'), night('2026-03-10')]))
            ->toThrow(StayUnavailableException::class);
    });

    it('refuses a unit without a price', function () {
        expect(fn () => (new StayPriceCalculator(baseSetting(), []))->priceUnit(SUITE, 1, [night('2026-03-10')]))
            ->toThrow(StayUnavailableException::class, __('booking.unpriced_on', ['date' => '2026-03-10']));
    });

    it('takes the minimum stay of the period of the first night', function () {
        $summer = periodSetting(pricingPeriod('summer', '2026-07-01', '2026-08-31'), [], ['min_stay' => 3]);
        $calculator = new StayPriceCalculator(baseSetting(), [$summer]);

        expect($calculator->minStay(night('2026-07-01')))->toBe(3)
            ->and($calculator->minStay(night('2026-06-30')))->toBe(1);
    });
});
