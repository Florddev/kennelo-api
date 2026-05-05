<?php

declare(strict_types=1);

use App\Enums\BookingStatus;
use App\Enums\ReviewerType;
use App\Models\Booking;
use App\Models\Establishment;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\ReviewCriteriaSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user']);
    Role::firstOrCreate(['name' => 'admin']);
    $this->seed(ReviewCriteriaSeeder::class);
});

function makeReviewFixtures(): array
{
    $user = User::factory()->create();
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create([
        'manager_id' => $manager->id,
        'is_active' => true,
    ]);
    $booking = Booking::factory()->completed()->create([
        'user_id' => $user->id,
        'establishment_id' => $establishment->id,
    ]);

    return [$user, $manager, $establishment, $booking];
}

// ─── store ────────────────────────────────────────────────────────────────────

it('user can review a completed booking', function () {
    [$user, $manager, $establishment, $booking] = makeReviewFixtures();

    $this->withHeaders(asUser($user))
        ->postJson("/api/bookings/{$booking->id}/reviews", [
            'overall_rating' => 4.5,
            'comment' => 'Très bon séjour',
            'would_recommend' => true,
            'criteria_scores' => [
                ['code' => 'cleanliness', 'score' => 5.0],
                ['code' => 'communication', 'score' => 4.5],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.reviewer_type', ReviewerType::USER->value)
        ->assertJsonPath('data.is_published', false);

    expect(Review::where('booking_id', $booking->id)->count())->toBe(1);
});

it('establishment manager can review the booking user', function () {
    [$user, $manager, $establishment, $booking] = makeReviewFixtures();

    $this->withHeaders(asUser($manager))
        ->postJson("/api/bookings/{$booking->id}/reviews", [
            'overall_rating' => 5.0,
            'comment' => 'Client agréable',
            'criteria_scores' => [
                ['code' => 'punctuality', 'score' => 5.0],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.reviewer_type', ReviewerType::ESTABLISHMENT->value);
});

it('cannot review a booking that is not completed', function () {
    [$user, $manager, $establishment, $booking] = makeReviewFixtures();
    $booking->update(['status' => BookingStatus::PENDING]);

    $this->withHeaders(asUser($user))
        ->postJson("/api/bookings/{$booking->id}/reviews", [
            'overall_rating' => 4.0,
        ])
        ->assertForbidden();
});

it('cannot create a duplicate review for the same direction', function () {
    [$user, $manager, $establishment, $booking] = makeReviewFixtures();

    Review::factory()->fromUser()->create([
        'booking_id' => $booking->id,
        'reviewer_id' => $user->id,
    ]);

    $this->withHeaders(asUser($user))
        ->postJson("/api/bookings/{$booking->id}/reviews", [
            'overall_rating' => 3.0,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['booking_id']);
});

it('rejects criteria not applicable to the reviewer direction', function () {
    [$user, $manager, $establishment, $booking] = makeReviewFixtures();

    $this->withHeaders(asUser($user))
        ->postJson("/api/bookings/{$booking->id}/reviews", [
            'overall_rating' => 4.0,
            'criteria_scores' => [
                ['code' => 'punctuality', 'score' => 4.0],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['criteria_scores']);
});

it('non-participant cannot review the booking', function () {
    [$user, $manager, $establishment, $booking] = makeReviewFixtures();
    $stranger = User::factory()->create();

    $this->withHeaders(asUser($stranger))
        ->postJson("/api/bookings/{$booking->id}/reviews", [
            'overall_rating' => 4.0,
        ])
        ->assertForbidden();
});

// ─── auto-publish on counterpart ──────────────────────────────────────────────

it('auto-publishes both reviews when both sides have reviewed', function () {
    [$user, $manager, $establishment, $booking] = makeReviewFixtures();

    Review::factory()->fromEstablishment()->create([
        'booking_id' => $booking->id,
        'reviewer_id' => $manager->id,
    ]);

    $this->withHeaders(asUser($user))
        ->postJson("/api/bookings/{$booking->id}/reviews", [
            'overall_rating' => 4.0,
        ])
        ->assertCreated();

    $reviews = Review::where('booking_id', $booking->id)->get();
    expect($reviews)->toHaveCount(2);
    expect($reviews->every(fn ($r) => $r->is_published === true))->toBeTrue();
});

// ─── public listing ───────────────────────────────────────────────────────────

it('lists published reviews for an establishment', function () {
    [$user, $manager, $establishment, $booking] = makeReviewFixtures();

    Review::factory()->fromUser()->published()->create([
        'booking_id' => $booking->id,
        'reviewer_id' => $user->id,
    ]);

    $other = User::factory()->create();
    $this->withHeaders(asUser($other))
        ->getJson("/api/establishments/{$establishment->id}/reviews")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('aggregates.total', 1);
});

it('does not list unpublished reviews to outsiders', function () {
    [$user, $manager, $establishment, $booking] = makeReviewFixtures();

    Review::factory()->fromUser()->create([
        'booking_id' => $booking->id,
        'reviewer_id' => $user->id,
        'is_published' => false,
    ]);

    $other = User::factory()->create();
    $this->withHeaders(asUser($other))
        ->getJson("/api/establishments/{$establishment->id}/reviews")
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

// ─── show / private feedback visibility ───────────────────────────────────────

it('hides private feedback from non-target viewers', function () {
    [$user, $manager, $establishment, $booking] = makeReviewFixtures();

    $review = Review::factory()->fromUser()->published()->create([
        'booking_id' => $booking->id,
        'reviewer_id' => $user->id,
        'private_feedback' => 'secret note for manager',
    ]);

    $other = User::factory()->create();
    $response = $this->withHeaders(asUser($other))
        ->getJson("/api/reviews/{$review->id}")
        ->assertOk();

    expect($response->json('data.private_feedback'))->toBeNull();
});

it('exposes private feedback to the target party', function () {
    [$user, $manager, $establishment, $booking] = makeReviewFixtures();

    $review = Review::factory()->fromUser()->published()->create([
        'booking_id' => $booking->id,
        'reviewer_id' => $user->id,
        'private_feedback' => 'secret note for manager',
    ]);

    $this->withHeaders(asUser($manager))
        ->getJson("/api/reviews/{$review->id}")
        ->assertOk()
        ->assertJsonPath('data.private_feedback', 'secret note for manager');
});

it('returns 403 on unpublished review for outsiders', function () {
    [$user, $manager, $establishment, $booking] = makeReviewFixtures();

    $review = Review::factory()->fromUser()->create([
        'booking_id' => $booking->id,
        'reviewer_id' => $user->id,
        'is_published' => false,
    ]);

    $other = User::factory()->create();
    $this->withHeaders(asUser($other))
        ->getJson("/api/reviews/{$review->id}")
        ->assertForbidden();
});

// ─── criteria endpoint ────────────────────────────────────────────────────────

it('lists review criteria definitions', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/review-criteria')
        ->assertOk()
        ->assertJsonStructure(['data' => [['code', 'label', 'applicable_to']]]);
});

// ─── unauth ───────────────────────────────────────────────────────────────────

it('unauthenticated user cannot create a review', function () {
    [$user, $manager, $establishment, $booking] = makeReviewFixtures();

    $this->postJson("/api/bookings/{$booking->id}/reviews", [
        'overall_rating' => 4.0,
    ])->assertUnauthorized();
});
