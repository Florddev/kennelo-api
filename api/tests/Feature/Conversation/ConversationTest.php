<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\Conversation;
use App\Models\Establishment;
use App\Models\Message;
use App\Models\User;

it('authenticated user can list their conversations', function () {
    $user = User::factory()->create();
    Conversation::factory()->count(2)->create(['user_id' => $user->id]);

    $this->withHeaders(asUser($user))
        ->getJson('/api/conversations')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('user only sees their own conversations', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Conversation::factory()->create(['user_id' => $user->id]);
    Conversation::factory()->create(['user_id' => $other->id]);

    $this->withHeaders(asUser($user))
        ->getJson('/api/conversations')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('unauthenticated user cannot list conversations', function () {
    $this->getJson('/api/conversations')->assertUnauthorized();
});

it('user can view their conversation', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    $this->withHeaders(asUser($user))
        ->getJson("/api/conversations/{$conversation->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $conversation->id);
});

it('user cannot view another user conversation', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $other->id]);

    $this->withHeaders(asUser($user))
        ->getJson("/api/conversations/{$conversation->id}")
        ->assertForbidden();
});

it('establishment manager can view conversation', function () {
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $conversation = Conversation::factory()->create(['establishment_id' => $establishment->id]);

    $this->withHeaders(asUser($manager))
        ->getJson("/api/conversations/{$conversation->id}")
        ->assertOk();
});

it('user can get or create conversation for their booking', function () {
    $user = User::factory()->create();
    $establishment = Establishment::factory()->create();
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'establishment_id' => $establishment->id,
    ]);

    $this->withHeaders(asUser($user))
        ->postJson("/api/bookings/{$booking->id}/conversation")
        ->assertOk()
        ->assertJsonPath('data.user_id', $user->id)
        ->assertJsonPath('data.establishment_id', $establishment->id);
});

it('user cannot create conversation for another user booking', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $booking = Booking::factory()->create(['user_id' => $other->id]);

    $this->withHeaders(asUser($user))
        ->postJson("/api/bookings/{$booking->id}/conversation")
        ->assertForbidden();
});

it('calling storeForBooking twice returns same conversation', function () {
    $user = User::factory()->create();
    $establishment = Establishment::factory()->create();
    $booking = Booking::factory()->create([
        'user_id' => $user->id,
        'establishment_id' => $establishment->id,
    ]);

    $response1 = $this->withHeaders(asUser($user))
        ->postJson("/api/bookings/{$booking->id}/conversation")
        ->assertOk();

    $response2 = $this->withHeaders(asUser($user))
        ->postJson("/api/bookings/{$booking->id}/conversation")
        ->assertOk();

    expect($response1->json('data.id'))->toBe($response2->json('data.id'));
});

it('establishment manager can list establishment conversations', function () {
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    Conversation::factory()->count(3)->create(['establishment_id' => $establishment->id]);

    $this->withHeaders(asUser($manager))
        ->getJson("/api/establishments/{$establishment->id}/conversations")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('non manager cannot list establishment conversations', function () {
    $user = User::factory()->create();
    $establishment = Establishment::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson("/api/establishments/{$establishment->id}/conversations")
        ->assertForbidden();
});

it('returns unread count', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $user->id]);

    Message::factory()->count(3)->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $other->id,
    ]);

    $this->withHeaders(asUser($user))
        ->getJson('/api/conversations/unread-count')
        ->assertOk()
        ->assertJsonPath('data.unread_count', 3);
});
