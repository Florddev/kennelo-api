<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\Booking;
use App\Models\Review;
use App\Models\ReviewResponse;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user']);
});

it('reviewee activity can respond to a user review', function () {
    $user = User::factory()->create();
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->completed()->create([
        'user_id' => $user->id,
        'activity_id' => $activity->id,
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

it('reviewee user can respond to an activity review', function () {
    $user = User::factory()->create();
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->completed()->create([
        'user_id' => $user->id,
        'activity_id' => $activity->id,
    ]);
    $review = Review::factory()->fromActivity()->published()->create([
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
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->completed()->create([
        'user_id' => $user->id,
        'activity_id' => $activity->id,
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
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $booking = Booking::factory()->completed()->create([
        'user_id' => $user->id,
        'activity_id' => $activity->id,
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
