<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Establishment;
use App\Models\User;

// ─── index ────────────────────────────────────────────────────────────────────

it('manager can list establishment bookings', function () {
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);

    Booking::factory()->count(3)->create(['establishment_id' => $establishment->id]);

    $this->withHeaders(asUser($manager))
        ->getJson("/api/establishments/{$establishment->id}/bookings")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('non-manager cannot list establishment bookings', function () {
    $manager = User::factory()->create();
    $other = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($other))
        ->getJson("/api/establishments/{$establishment->id}/bookings")
        ->assertForbidden();
});

it('unauthenticated user cannot list establishment bookings', function () {
    $establishment = Establishment::factory()->create();

    $this->getJson("/api/establishments/{$establishment->id}/bookings")
        ->assertUnauthorized();
});

// ─── confirm ──────────────────────────────────────────────────────────────────

it('manager can confirm a pending booking', function () {
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->pending()->create(['establishment_id' => $establishment->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/establishments/{$establishment->id}/bookings/{$booking->id}/confirm")
        ->assertOk()
        ->assertJsonPath('data.status', BookingStatus::CONFIRMED->value);
});

it('manager cannot confirm an already confirmed booking', function () {
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->confirmed()->create(['establishment_id' => $establishment->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/establishments/{$establishment->id}/bookings/{$booking->id}/confirm")
        ->assertUnprocessable();
});

// ─── cancel ───────────────────────────────────────────────────────────────────

it('manager can cancel a pending booking', function () {
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->pending()->create(['establishment_id' => $establishment->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/establishments/{$establishment->id}/bookings/{$booking->id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', BookingStatus::CANCELLED->value);
});

// ─── complete ─────────────────────────────────────────────────────────────────

it('manager can complete a confirmed booking', function () {
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->confirmed()->create(['establishment_id' => $establishment->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/establishments/{$establishment->id}/bookings/{$booking->id}/complete")
        ->assertOk()
        ->assertJsonPath('data.status', BookingStatus::COMPLETED->value);
});

it('manager cannot complete a pending booking', function () {
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->pending()->create(['establishment_id' => $establishment->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/establishments/{$establishment->id}/bookings/{$booking->id}/complete")
        ->assertUnprocessable();
});

it('manager cannot act on a booking from another establishment', function () {
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $otherEstablishment = Establishment::factory()->create();
    $booking = Booking::factory()->pending()->create(['establishment_id' => $otherEstablishment->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/establishments/{$establishment->id}/bookings/{$booking->id}/confirm")
        ->assertNotFound();
});
