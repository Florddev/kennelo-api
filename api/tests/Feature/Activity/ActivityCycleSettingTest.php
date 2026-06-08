<?php

declare(strict_types=1);

use App\Enums\WeekDayEnum;
use App\Models\Activity;
use App\Models\ActivityCycle;
use App\Models\ActivityCycleSetting;
use App\Models\AnimalType;
use App\Models\User;

function makeCycleSettingFixtures(): array
{
    $activity = Activity::factory()->create(['is_active' => true]);
    $animalType = AnimalType::create(['code' => 'dog_'.uniqid(), 'name' => 'Chien', 'category' => 'mammals']);

    $cycle = ActivityCycle::create([
        'activity_id' => $activity->id,
        'is_active' => true,
        'priority' => 0,
        'start_date' => null,
        'end_date' => null,
    ]);

    ActivityCycleSetting::create([
        'activity_cycle_id' => $cycle->id,
        'animal_type_id' => $animalType->id,
        'max_capacity' => 8,
        'price' => 25.00,
        'sum_weekdays' => WeekDayEnum::ALL,
    ]);

    return [$activity, $animalType];
}

it('any authenticated user can read the active cycle settings', function () {
    $user = User::factory()->create();
    [$activity] = makeCycleSettingFixtures();

    $this->withHeaders(asUser($user))
        ->getJson("/api/activities/{$activity->id}/cycle-settings")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.max_capacity', 8)
        ->assertJsonPath('data.0.available_spots', 8);
});

it('cycle settings can be requested for a specific date', function () {
    $user = User::factory()->create();
    [$activity] = makeCycleSettingFixtures();

    $this->withHeaders(asUser($user))
        ->getJson("/api/activities/{$activity->id}/cycle-settings?date=2026-07-15")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('cycle settings reject an invalid date format', function () {
    $user = User::factory()->create();
    [$activity] = makeCycleSettingFixtures();

    $this->withHeaders(asUser($user))
        ->getJson("/api/activities/{$activity->id}/cycle-settings?date=15-07-2026")
        ->assertUnprocessable();
});

it('unauthenticated user cannot read cycle settings', function () {
    [$activity] = makeCycleSettingFixtures();

    $this->getJson("/api/activities/{$activity->id}/cycle-settings")->assertUnauthorized();
});
