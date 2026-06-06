<?php

declare(strict_types=1);

use App\Enums\AvailabilityStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\MessageTypeEnum;
use App\Enums\SenderTypeEnum;
use App\Models\AnimalType;
use App\Models\Booking;
use App\Models\BookingThread;
use App\Models\Establishment;
use App\Models\EstablishmentAvailability;
use App\Models\EstablishmentCapacity;
use App\Models\Message;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Stripe\StripeClient;
use Tests\Support\FakeStripeClient;

function makeBookingFixtures(): array
{
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create([
        'manager_id' => $manager->id,
        'is_active' => true,
        'stripe_account_id' => 'acct_test_'.uniqid(),
        'stripe_charges_enabled' => true,
    ]);
    $animalType = AnimalType::create(['code' => 'dog_'.uniqid(), 'name' => 'Chien', 'category' => 'mammals']);

    EstablishmentCapacity::create([
        'establishment_id' => $establishment->id,
        'animal_type_id' => $animalType->id,
        'max_capacity' => 5,
        'price_per_night' => 30.00,
    ]);

    return [$manager, $establishment, $animalType];
}

function fakeStripe(): void
{
    app()->instance(StripeClient::class, new FakeStripeClient);
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
    Event::fake();
    fakeStripe();

    $user = User::factory()->create();
    [$manager, $establishment, $animalType] = makeBookingFixtures();
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);

    $this->withHeaders(asUser($user))
        ->postJson('/api/bookings', [
            'establishment_id' => $establishment->id,
            'check_in_date' => now()->addDays(10)->format('Y-m-d'),
            'check_out_date' => now()->addDays(13)->format('Y-m-d'),
            'pet_ids' => [$pet->id],
            'payment_method_id' => 'pm_card_visa',
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', BookingStatusEnum::PENDING->value)
        ->assertJsonPath('data.establishment_id', $establishment->id);
});

it('booking creation fails if a day is closed', function () {
    $user = User::factory()->create();
    [$manager, $establishment, $animalType] = makeBookingFixtures();
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);

    EstablishmentAvailability::create([
        'establishment_id' => $establishment->id,
        'date' => now()->addDays(11)->format('Y-m-d'),
        'status' => AvailabilityStatusEnum::CLOSED,
    ]);

    $this->withHeaders(asUser($user))
        ->postJson('/api/bookings', [
            'establishment_id' => $establishment->id,
            'check_in_date' => now()->addDays(10)->format('Y-m-d'),
            'check_out_date' => now()->addDays(13)->format('Y-m-d'),
            'pet_ids' => [$pet->id],
            'payment_method_id' => 'pm_card_visa',
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
            'payment_method_id' => 'pm_card_visa',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['pet_ids']);
});

it('unauthenticated user cannot create a booking', function () {
    $this->postJson('/api/bookings', [])->assertUnauthorized();
});

it('booking creation automatically creates a thread and a booking reference message', function () {
    Event::fake();
    fakeStripe();

    $user = User::factory()->create();
    [$manager, $establishment, $animalType] = makeBookingFixtures();
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);

    $this->withHeaders(asUser($user))
        ->postJson('/api/bookings', [
            'establishment_id' => $establishment->id,
            'check_in_date' => now()->addDays(10)->format('Y-m-d'),
            'check_out_date' => now()->addDays(13)->format('Y-m-d'),
            'pet_ids' => [$pet->id],
            'payment_method_id' => 'pm_card_visa',
        ])
        ->assertCreated();

    $booking = Booking::where('user_id', $user->id)->first();

    expect(BookingThread::where('booking_id', $booking->id)->exists())->toBeTrue();
    expect(
        Message::where('booking_id', $booking->id)
            ->where('message_type', MessageTypeEnum::BOOKING_REFERENCE->value)
            ->where('sender_type', SenderTypeEnum::USER->value)
            ->exists()
    )->toBeTrue();
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
        ->assertJsonPath('data.status', BookingStatusEnum::CANCELLED->value);
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
