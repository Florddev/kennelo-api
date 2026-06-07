<?php

declare(strict_types=1);

use App\Enums\WeekDayEnum;
use App\Models\Activity;
use App\Models\ActivityCycle;
use App\Models\ActivityCycleSetting;
use App\Models\AnimalType;
use App\Models\Pet;
use App\Models\User;

function makeQuoteFixtures(): array
{
    $manager = User::factory()->create();
    $activity = Activity::factory()->create([
        'manager_id' => $manager->id,
        'is_active' => true,
    ]);
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
        'max_capacity' => 5,
        'price' => 30.00,
        'sum_weekdays' => WeekDayEnum::ALL,
    ]);

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
        ->assertJsonPath('data.total_price', '90.00')
        ->assertJsonPath('data.platform_fee', '9.00')
        ->assertJsonPath('data.activity_amount', '81.00')
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

it('unauthenticated user cannot request a quote', function () {
    $this->postJson('/api/bookings/quote', [])->assertUnauthorized();
});
