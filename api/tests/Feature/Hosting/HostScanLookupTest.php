<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Models\Activity;
use App\Models\AnimalType;
use App\Models\Booking;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Support\Str;

function scannedPet(User $owner): Pet
{
    $animalType = AnimalType::create(['code' => 'dog_'.Str::random(8), 'name' => 'Chien', 'category' => 'mammals']);

    return Pet::create([
        'user_id' => $owner->id,
        'animal_type_id' => $animalType->id,
        'name' => 'Rex',
        'microchip_number' => '250269'.random_int(100000000, 999999999),
        'has_microchip' => true,
    ]);
}

it('returns the owner contact when the host has the pet in care', function () {
    $host = User::factory()->create();
    $owner = User::factory()->create(['phone' => '+33612345678']);
    $activity = Activity::factory()->create(['manager_id' => $host->id]);
    $pet = scannedPet($owner);

    $booking = Booking::factory()->create([
        'user_id' => $owner->id,
        'activity_id' => $activity->id,
        'status' => BookingStatusEnum::CONFIRMED,
        'check_in_date' => now()->subDay()->format('Y-m-d'),
        'check_out_date' => now()->addDay()->format('Y-m-d'),
    ]);
    $booking->pets()->attach($pet->id, [
        'price_per_night' => '30.00',
        'number_of_nights' => 2,
        'subtotal' => '60.00',
    ]);

    $this->withHeaders(asUser($host))
        ->getJson("/api/hosting/scan-lookup/{$pet->microchip_number}")
        ->assertOk()
        ->assertJsonPath('found', true)
        ->assertJsonPath('owner.phone', '+33612345678');
});

it('hides the owner contact when the host has no care relationship', function () {
    $host = User::factory()->create();
    $owner = User::factory()->create(['phone' => '+33612345678']);
    Activity::factory()->create(['manager_id' => $host->id]);
    $pet = scannedPet($owner);

    $this->withHeaders(asUser($host))
        ->getJson("/api/hosting/scan-lookup/{$pet->microchip_number}")
        ->assertOk()
        ->assertJsonPath('found', true)
        ->assertJsonPath('owner', null);
});
