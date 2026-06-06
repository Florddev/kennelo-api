<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Enums\MessageTypeEnum;
use App\Enums\SenderTypeEnum;
use App\Models\Booking;
use App\Models\BookingThread;
use App\Models\Conversation;
use App\Models\Establishment;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Event;

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
        ->assertJsonPath('data.status', BookingStatusEnum::CONFIRMED->value);
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
        ->assertJsonPath('data.status', BookingStatusEnum::CANCELLED->value);
});

// ─── complete ─────────────────────────────────────────────────────────────────

it('manager can complete a confirmed booking', function () {
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->confirmed()->create(['establishment_id' => $establishment->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/establishments/{$establishment->id}/bookings/{$booking->id}/complete")
        ->assertOk()
        ->assertJsonPath('data.status', BookingStatusEnum::COMPLETED->value);
});

it('manager cannot complete a pending booking', function () {
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->pending()->create(['establishment_id' => $establishment->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/establishments/{$establishment->id}/bookings/{$booking->id}/complete")
        ->assertUnprocessable();
});

it('confirming a booking sends a booking reference message from the establishment', function () {
    Event::fake();

    $manager = User::factory()->create();
    $user = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->pending()->create([
        'user_id' => $user->id,
        'establishment_id' => $establishment->id,
    ]);

    $conversation = Conversation::create(['user_id' => $user->id, 'establishment_id' => $establishment->id]);
    BookingThread::create(['booking_id' => $booking->id, 'conversation_id' => $conversation->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/establishments/{$establishment->id}/bookings/{$booking->id}/confirm")
        ->assertOk();

    expect(
        Message::where('booking_id', $booking->id)
            ->where('message_type', MessageTypeEnum::BOOKING_REFERENCE->value)
            ->where('sender_type', SenderTypeEnum::ESTABLISHMENT->value)
            ->exists()
    )->toBeTrue();
});

it('cancelling a booking sends a booking reference message from the establishment', function () {
    Event::fake();

    $manager = User::factory()->create();
    $user = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->pending()->create([
        'user_id' => $user->id,
        'establishment_id' => $establishment->id,
    ]);

    $conversation = Conversation::create(['user_id' => $user->id, 'establishment_id' => $establishment->id]);
    BookingThread::create(['booking_id' => $booking->id, 'conversation_id' => $conversation->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/establishments/{$establishment->id}/bookings/{$booking->id}/cancel")
        ->assertOk();

    expect(
        Message::where('booking_id', $booking->id)
            ->where('message_type', MessageTypeEnum::BOOKING_REFERENCE->value)
            ->where('sender_type', SenderTypeEnum::ESTABLISHMENT->value)
            ->exists()
    )->toBeTrue();
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
