<?php

declare(strict_types=1);

use App\Enums\AvailabilityStatusEnum;
use App\Enums\WeekDayEnum;
use App\Models\Activity;
use App\Models\ActivityUnitType;
use App\Models\PricingPeriod;

describe('settings and grid', function () {
    it('lists every period of the company with the setting of the activity', function () {
        $unitType = dogBoarding();
        $summer = PricingPeriod::factory()->for($unitType->activity->organization)->create();

        $this->withHeaders(asUser($unitType->activity->organization->owner))
            ->getJson("/api/activities/{$unitType->activity_id}/pricing-periods")
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.setting.prices.0', [
                'unit_type_id' => $unitType->id,
                'weekday' => null,
                'price' => '30.00',
                'extra_animal_price' => null,
            ])
            ->assertJsonPath('1.id', $summer->id)
            ->assertJsonPath('1.setting', null);
    });

    it('configures a period for the activity', function () {
        $unitType = dogBoarding();
        $summer = PricingPeriod::factory()->for($unitType->activity->organization)->create();

        $this->withHeaders(asUser($unitType->activity->organization->owner))
            ->putJson("/api/activities/{$unitType->activity_id}/pricing-periods/{$summer->id}", [
                'min_stay' => 3,
                'price_modifier_percent' => '15',
                'closed_weekdays' => [WeekDayEnum::SUNDAY->value, WeekDayEnum::MONDAY->value],
            ])
            ->assertOk()
            ->assertJsonPath('is_active', true)
            ->assertJsonPath('min_stay', 3)
            ->assertJsonPath('price_modifier_percent', '15.00')
            ->assertJsonPath('closed_weekdays', [WeekDayEnum::MONDAY->value, WeekDayEnum::SUNDAY->value]);
    });

    it('replaces the grid of a period', function () {
        $unitType = dogBoarding();
        $base = $unitType->activity->organization->pricingPeriods()->base()->sole();

        $this->withHeaders(asUser($unitType->activity->organization->owner))
            ->putJson("/api/activities/{$unitType->activity_id}/pricing-periods/{$base->id}/prices", ['prices' => [
                ['unit_type_id' => $unitType->id, 'price' => '28.00', 'extra_animal_price' => '12.00'],
                ['unit_type_id' => $unitType->id, 'weekday' => WeekDayEnum::SATURDAY->value, 'price' => '35.00'],
            ]])
            ->assertOk()
            ->assertJsonCount(2, 'prices')
            ->assertJsonPath('prices.0.price', '28.00');
    });

    it('refuses a unit of another activity and a unit priced twice for the same day', function () {
        $unitType = dogBoarding();
        $other = dogBoarding();
        $base = $unitType->activity->organization->pricingPeriods()->base()->sole();
        $headers = asUser($unitType->activity->organization->owner);

        $this->withHeaders($headers)
            ->putJson("/api/activities/{$unitType->activity_id}/pricing-periods/{$base->id}/prices", ['prices' => [
                ['unit_type_id' => $other->id, 'price' => '28.00'],
            ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['prices.0.unit_type_id']);

        $this->withHeaders($headers)
            ->putJson("/api/activities/{$unitType->activity_id}/pricing-periods/{$base->id}/prices", ['prices' => [
                ['unit_type_id' => $unitType->id, 'price' => '28.00'],
                ['unit_type_id' => $unitType->id, 'price' => '29.00'],
            ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['prices' => __('booking.duplicate_price')]);
    });

    it('does not apply the period of another company', function () {
        $unitType = dogBoarding();
        $foreign = PricingPeriod::factory()->create();

        $this->withHeaders(asUser($unitType->activity->organization->owner))
            ->putJson("/api/activities/{$unitType->activity_id}/pricing-periods/{$foreign->id}", ['min_stay' => 2])
            ->assertNotFound();
    });
});

describe('price calendar', function () {
    it('prices each day with the period it falls in, for the active units', function () {
        $unitType = dogBoarding(units: 3);
        $activity = $unitType->activity;
        $from = today()->addDays(10);
        $summer = PricingPeriod::factory()->for($activity->organization)
            ->between($from->copy()->addDay()->toDateString(), $from->copy()->addDays(5)->toDateString())
            ->create();
        ActivityUnitType::factory()->for($activity)->create(['name' => 'Suite', 'is_active' => false]);

        $this->withHeaders(asUser($activity->organization->owner))
            ->putJson("/api/activities/{$activity->id}/pricing-periods/{$summer->id}", ['price_modifier_percent' => '10'])
            ->assertOk();

        $this->getJson("/api/activities/{$activity->id}/price-calendar?from={$from->toDateString()}&to={$from->copy()->addDay()->toDateString()}")
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.is_open', true)
            ->assertJsonPath('0.min_stay', 1)
            ->assertJsonPath('0.units', [['unit_type_id' => $unitType->id, 'price' => '30.00', 'available' => 3]])
            ->assertJsonPath('1.units', [['unit_type_id' => $unitType->id, 'price' => '33.00', 'available' => 3]]);
    });

    it('marks the days the activity is closed', function () {
        $activity = dogBoarding()->activity;
        $day = today()->addDays(10);
        $activity->availabilities()->create(['date' => $day->toDateString(), 'status' => AvailabilityStatusEnum::CLOSED]);

        $this->getJson("/api/activities/{$activity->id}/price-calendar?from={$day->toDateString()}&to={$day->toDateString()}")
            ->assertOk()
            ->assertJsonPath('0', ['date' => $day->toDateString(), 'is_open' => false, 'min_stay' => null, 'units' => []]);
    });

    it('stops at 93 days', function () {
        $activity = dogBoarding()->activity;
        $from = today();

        $this->getJson("/api/activities/{$activity->id}/price-calendar?from={$from->toDateString()}&to={$from->copy()->addDays(93)->toDateString()}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
    });

    it('is not public for an activity that cannot be booked', function () {
        $activity = Activity::factory()->create();

        $this->getJson("/api/activities/{$activity->id}/price-calendar?from=2026-10-01&to=2026-10-02")
            ->assertNotFound();
    });
});

describe('after a downgrade', function () {
    it('only applies the oldest periods within the quota when the option is on', function () {
        $unitType = dogBoarding();
        $organization = $unitType->activity->organization;
        $day = today()->addDays(20)->toDateString();
        PricingPeriod::factory()->for($organization)->count(3)->between($day, today()->addDays(40)->toDateString())->create(['created_at' => now()->subDay()]);
        $newest = PricingPeriod::factory()->for($organization)->between($day, $day)->create();

        $this->withHeaders(asUser($organization->owner))
            ->putJson("/api/activities/{$unitType->activity_id}/pricing-periods/{$newest->id}/prices", [
                'prices' => [['unit_type_id' => $unitType->id, 'price' => '99.00']],
            ])
            ->assertOk();

        $url = "/api/activities/{$unitType->activity_id}/price-calendar?from={$day}&to={$day}";

        $this->getJson($url)->assertJsonPath('0.units.0.price', '99.00');

        config(['plans.downgrade.soft_disable.periods' => true]);

        $this->getJson($url)->assertJsonPath('0.units.0.price', '30.00');
    });
});
