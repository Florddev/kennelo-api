<?php

declare(strict_types=1);

use App\Enums\AvailabilityStatus;
use App\Enums\BookingStatus;
use App\Models\AnimalType;
use App\Models\Booking;
use App\Models\Establishment;
use App\Models\EstablishmentAvailability;
use App\Models\EstablishmentCapacity;
use App\Models\Pet;
use App\Models\User;

function makeBookingFixtures(): array
{
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id, 'is_active' => true]);
    $animalType = AnimalType::create(['code' => 'dog_'.uniqid(), 'name' => 'Chien', 'category' => 'mammals']);

    EstablishmentCapacity::create([
        'establishment_id' => $establishment->id,
        'animal_type_id' => $animalType->id,
        'max_capacity' => 5,
        'price_per_night' => 30.00,
    ]);

    return [$manager, $establishment, $animalType];
}

// ─── index ────────────────────────────────────────────────────────────────────

it('authenticated user can list their bookings', function () {
    $user = User::factory()->create();
    Booking::factory()->count(2)->create(['user_id' => $user->id]);

    $this->withHeaders(asUser($user))
        ->getJson('/api/bookings')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('user only sees their own bookings', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Booking::factory()->create(['user_id' => $user->id]);
    Booking::factory()->create(['user_id' => $other->id]);

    $this->withHeaders(asUser($user))
        ->getJson('/api/bookings')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('unauthenticated user cannot list bookings', function () {
    $this->getJson('/api/bookings')->assertUnauthorized();
});

// ─── show ─────────────────────────────────────────────────────────────────────

it('owner can view their booking', function () {
    $user = User::factory()->create();
    $booking = Booking::factory()->create(['user_id' => $user->id]);

    $this->withHeaders(asUser($user))
        ->getJson("/api/bookings/{$booking->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $booking->id);
});

it('user cannot view another user booking', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $booking = Booking::factory()->create(['user_id' => $other->id]);

    $this->withHeaders(asUser($user))
        ->getJson("/api/bookings/{$booking->id}")
        ->assertForbidden();
});

// ─── store ────────────────────────────────────────────────────────────────────

it('authenticated user can create a booking', function () {
    $user = User::factory()->create();
    [$manager, $establishment, $animalType] = makeBookingFixtures();
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);

    $this->withHeaders(asUser($user))
        ->postJson('/api/bookings', [
            'establishment_id' => $establishment->id,
            'check_in_date' => now()->addDays(10)->format('Y-m-d'),
            'check_out_date' => now()->addDays(13)->format('Y-m-d'),
            'pet_ids' => [$pet->id],
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', BookingStatus::PENDING->value)
        ->assertJsonPath('data.establishment_id', $establishment->id);
});

it('booking creation fails if a day is closed', function () {
    $user = User::factory()->create();
    [$manager, $establishment, $animalType] = makeBookingFixtures();
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);

    EstablishmentAvailability::create([
        'establishment_id' => $establishment->id,
        'date' => now()->addDays(11)->format('Y-m-d'),
        'status' => AvailabilityStatus::CLOSED,
    ]);

    $this->withHeaders(asUser($user))
        ->postJson('/api/bookings', [
            'establishment_id' => $establishment->id,
            'check_in_date' => now()->addDays(10)->format('Y-m-d'),
            'check_out_date' => now()->addDays(13)->format('Y-m-d'),
            'pet_ids' => [$pet->id],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['check_in_date']);
});

it('booking creation fails if capacity is exceeded', function () {
    $user = User::factory()->create();
    [$manager, $establishment, $animalType] = makeBookingFixtures();

    EstablishmentCapacity::where('establishment_id', $establishment->id)
        ->where('animal_type_id', $animalType->id)
        ->update(['max_capacity' => 1]);

    $pet1 = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);
    $pet2 = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Max']);

    $this->withHeaders(asUser($user))
        ->postJson('/api/bookings', [
            'establishment_id' => $establishment->id,
            'check_in_date' => now()->addDays(10)->format('Y-m-d'),
            'check_out_date' => now()->addDays(13)->format('Y-m-d'),
            'pet_ids' => [$pet1->id, $pet2->id],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['pet_ids']);
});

it('unauthenticated user cannot create a booking', function () {
    $this->postJson('/api/bookings', [])->assertUnauthorized();
});

it('user cannot book with another user pets', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    [$manager, $establishment, $animalType] = makeBookingFixtures();
    $pet = Pet::create(['user_id' => $other->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);

    $this->withHeaders(asUser($user))
        ->postJson('/api/bookings', [
            'establishment_id' => $establishment->id,
            'check_in_date' => now()->addDays(10)->format('Y-m-d'),
            'check_out_date' => now()->addDays(13)->format('Y-m-d'),
            'pet_ids' => [$pet->id],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['pet_ids.0']);
});

// ─── cancel ───────────────────────────────────────────────────────────────────

it('user can cancel their pending booking', function () {
    $user = User::factory()->create();
    $booking = Booking::factory()->pending()->create(['user_id' => $user->id]);

    $this->withHeaders(asUser($user))
        ->putJson("/api/bookings/{$booking->id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', BookingStatus::CANCELLED->value);
});

it('user cannot cancel a completed booking', function () {
    $user = User::factory()->create();
    $booking = Booking::factory()->completed()->create(['user_id' => $user->id]);

    $this->withHeaders(asUser($user))
        ->putJson("/api/bookings/{$booking->id}/cancel")
        ->assertForbidden();
});

it('user cannot cancel another user booking', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $booking = Booking::factory()->pending()->create(['user_id' => $other->id]);

    $this->withHeaders(asUser($user))
        ->putJson("/api/bookings/{$booking->id}/cancel")
        ->assertForbidden();
});
