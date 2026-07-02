<?php

declare(strict_types=1);

use App\Enums\WeekDayEnum;
use App\Models\Activity;
use App\Models\ActivityCycle;
use App\Models\ActivityCycleSetting;
use App\Models\AnimalType;
use App\Models\Pet;
use App\Models\User;

function makeQuoteFixtures(?callable $priceFor = null, int $closedMask = 0): array
{
    $manager = User::factory()->create();
    $activity = Activity::factory()->create([
        'manager_id' => $manager->id,
        'is_active' => true,
        'stripe_account_id' => 'acct_test_'.uniqid(),
        'stripe_charges_enabled' => true,
        'stripe_payouts_enabled' => true,
        'stripe_onboarding_completed' => true,
    ]);
    $animalType = AnimalType::create(['code' => 'dog_'.uniqid(), 'name' => 'Chien', 'category' => 'mammals']);

    $cycle = ActivityCycle::create([
        'activity_id' => $activity->id,
        'is_active' => true,
        'priority' => 0,
        'start_date' => null,
        'end_date' => null,
    ]);

    $setting = ActivityCycleSetting::create([
        'activity_cycle_id' => $cycle->id,
        'animal_type_id' => $animalType->id,
        'max_capacity' => 5,
    ]);

    foreach (WeekDayEnum::values() as $weekday) {
        $setting->prices()->create([
            'weekday' => $weekday,
            'price' => $priceFor !== null ? $priceFor($weekday) : 30.00,
        ]);
    }

    if ($closedMask > 0) {
        $cycle->closedWeekDays()->create(['sum_weekdays' => $closedMask]);
    }

    return [$activity, $animalType];
}

it('returns a price quote for the selected dates and pets', function () {
    $user = User::factory()->create();
    [$activity, $animalType] = makeQuoteFixtures();
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);

    $this->withHeaders(asUser($user))
        ->postJson('/api/bookings/quote', [
            'activity_id' => $activity->id,
            'check_in_date' => now()->addDays(10)->format('Y-m-d'),
            'check_out_date' => now()->addDays(13)->format('Y-m-d'),
            'pet_ids' => [$pet->id],
        ])
        ->assertOk()
        ->assertJsonPath('data.nights', 3)
        ->assertJsonPath('data.total_price', '97.20')
        ->assertJsonPath('data.service_fee', '7.20')
        ->assertJsonPath('data.platform_fee', '5.40')
        ->assertJsonPath('data.activity_amount', '84.60')
        ->assertJsonPath('data.pets.0.price_per_night', '30.00')
        ->assertJsonPath('data.pets.0.subtotal', '90.00');
});

it('rejects a quote for an animal type the activity does not accept', function () {
    $user = User::factory()->create();
    [$activity] = makeQuoteFixtures();
    $otherType = AnimalType::create(['code' => 'cat_'.uniqid(), 'name' => 'Chat', 'category' => 'mammals']);
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $otherType->id, 'name' => 'Whiskers']);

    $this->withHeaders(asUser($user))
        ->postJson('/api/bookings/quote', [
            'activity_id' => $activity->id,
            'check_in_date' => now()->addDays(10)->format('Y-m-d'),
            'check_out_date' => now()->addDays(13)->format('Y-m-d'),
            'pet_ids' => [$pet->id],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['pet_ids']);
});

it('prices a single night using its weekday price', function () {
    $user = User::factory()->create();
    [$activity, $animalType] = makeQuoteFixtures(
        fn (int $weekday): float => $weekday === WeekDayEnum::MONDAY->value ? 10.00 : 25.00,
    );
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);

    $monday = now()->addWeeks(2)->startOfWeek();

    $this->withHeaders(asUser($user))
        ->postJson('/api/bookings/quote', [
            'activity_id' => $activity->id,
            'check_in_date' => $monday->toDateString(),
            'check_out_date' => $monday->copy()->addDay()->toDateString(),
            'pet_ids' => [$pet->id],
        ])
        ->assertOk()
        ->assertJsonPath('data.total_price', '10.80');
});

it('rejects a quote when a night falls on a cycle-closed weekday', function () {
    $user = User::factory()->create();
    [$activity, $animalType] = makeQuoteFixtures(null, WeekDayEnum::MONDAY->value);
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);

    $monday = now()->addWeeks(2)->startOfWeek();

    $this->withHeaders(asUser($user))
        ->postJson('/api/bookings/quote', [
            'activity_id' => $activity->id,
            'check_in_date' => $monday->toDateString(),
            'check_out_date' => $monday->copy()->addDay()->toDateString(),
            'pet_ids' => [$pet->id],
        ])
        ->assertUnprocessable();
});

it('unauthenticated user cannot request a quote', function () {
    $this->postJson('/api/bookings/quote', [])->assertUnauthorized();
});
