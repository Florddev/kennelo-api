<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\Establishment;
use App\Models\Review;
use App\Models\ReviewResponse;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user']);
});

it('reviewee establishment can respond to a user review', function () {
    $user = User::factory()->create();
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->completed()->create([
        'user_id' => $user->id,
        'establishment_id' => $establishment->id,
    ]);
    $review = Review::factory()->fromUser()->published()->create([
        'booking_id' => $booking->id,
        'reviewer_id' => $user->id,
    ]);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/reviews/{$review->id}/response", [
            'response' => 'Merci pour votre retour !',
        ])
        ->assertCreated()
        ->assertJsonPath('data.response', 'Merci pour votre retour !');
});

it('reviewee user can respond to an establishment review', function () {
    $user = User::factory()->create();
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->completed()->create([
        'user_id' => $user->id,
        'establishment_id' => $establishment->id,
    ]);
    $review = Review::factory()->fromEstablishment()->published()->create([
        'booking_id' => $booking->id,
        'reviewer_id' => $manager->id,
    ]);

    $this->withHeaders(asUser($user))
        ->postJson("/api/reviews/{$review->id}/response", [
            'response' => 'Merci !',
        ])
        ->assertCreated();
});

it('reviewer cannot respond to its own review', function () {
    $user = User::factory()->create();
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->completed()->create([
        'user_id' => $user->id,
        'establishment_id' => $establishment->id,
    ]);
    $review = Review::factory()->fromUser()->published()->create([
        'booking_id' => $booking->id,
        'reviewer_id' => $user->id,
    ]);

    $this->withHeaders(asUser($user))
        ->postJson("/api/reviews/{$review->id}/response", [
            'response' => 'Self response',
        ])
        ->assertForbidden();
});

it('cannot create more than one response per review', function () {
    $user = User::factory()->create();
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->completed()->create([
        'user_id' => $user->id,
        'establishment_id' => $establishment->id,
    ]);
    $review = Review::factory()->fromUser()->published()->create([
        'booking_id' => $booking->id,
        'reviewer_id' => $user->id,
    ]);

    ReviewResponse::create([
        'review_id' => $review->id,
        'responder_id' => $manager->id,
        'response' => 'First',
    ]);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/reviews/{$review->id}/response", [
            'response' => 'Second',
        ])
        ->assertUnprocessable();
});
