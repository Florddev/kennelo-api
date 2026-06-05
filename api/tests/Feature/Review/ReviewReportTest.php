<?php

declare(strict_types=1);

use App\Enums\ReviewReportStatusEnum;
use App\Models\Booking;
use App\Models\Establishment;
use App\Models\Review;
use App\Models\ReviewReport;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'user']);
    Role::firstOrCreate(['name' => 'admin']);
});

function makeReviewForReportFixtures(): array
{
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

    return [$user, $manager, $establishment, $review];
}

it('any authenticated non-author user can report a review', function () {
    [$user, $manager, $establishment, $review] = makeReviewForReportFixtures();

    $this->withHeaders(asUser($manager))
        ->postJson("/api/reviews/{$review->id}/reports", [
            'reason' => 'inappropriate',
            'description' => 'Contenu offensant',
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', ReviewReportStatusEnum::PENDING->value);
});

it('reviewer cannot report their own review', function () {
    [$user, $manager, $establishment, $review] = makeReviewForReportFixtures();

    $this->withHeaders(asUser($user))
        ->postJson("/api/reviews/{$review->id}/reports", [
            'reason' => 'spam',
        ])
        ->assertForbidden();
});

it('cannot report the same review twice', function () {
    [$user, $manager, $establishment, $review] = makeReviewForReportFixtures();

    ReviewReport::create([
        'review_id' => $review->id,
        'reporter_id' => $manager->id,
        'reason' => 'spam',
        'status' => ReviewReportStatusEnum::PENDING->value,
    ]);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/reviews/{$review->id}/reports", [
            'reason' => 'spam',
        ])
        ->assertUnprocessable();
});

it('admin can list review reports', function () {
    [$user, $manager, $establishment, $review] = makeReviewForReportFixtures();

    ReviewReport::create([
        'review_id' => $review->id,
        'reporter_id' => $manager->id,
        'reason' => 'spam',
        'status' => ReviewReportStatusEnum::PENDING->value,
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->withHeaders(asUser($admin))
        ->getJson('/api/admin/review-reports')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('non-admin cannot list review reports', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->withHeaders(asUser($user))
        ->getJson('/api/admin/review-reports')
        ->assertForbidden();
});

it('admin can update report status', function () {
    [$user, $manager, $establishment, $review] = makeReviewForReportFixtures();

    $report = ReviewReport::create([
        'review_id' => $review->id,
        'reporter_id' => $manager->id,
        'reason' => 'spam',
        'status' => ReviewReportStatusEnum::PENDING->value,
    ]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->withHeaders(asUser($admin))
        ->putJson("/api/admin/review-reports/{$report->id}", [
            'status' => 'reviewed',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', ReviewReportStatusEnum::REVIEWED->value);
});
