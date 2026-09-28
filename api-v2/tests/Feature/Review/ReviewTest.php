<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\OrganizationRoleEnum;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Séjour terminé il y a deux jours.
 */
function completedStay(?Activity $activity = null): Booking
{
    return Booking::factory()
        ->confirmed()
        ->status(BookingStatusEnum::COMPLETED)
        ->between(today()->subDays(4)->toDateString(), today()->subDays(2)->toDateString())
        ->create($activity === null ? [] : ['activity_id' => $activity->id]);
}

describe('giving a review', function () {
    it('lets the client review the activity, hidden until the team reviews too', function () {
        Notification::fake();
        $booking = completedStay();
        $manager = memberOf($booking->organization, OrganizationRoleEnum::ACTIVITY_MANAGER, $booking->activity_id);

        $this->withHeaders(asUser($booking->user))
            ->postJson("/api/bookings/{$booking->id}/reviews", ['overall_rating' => 4.5, 'comment' => 'Rex est revenu ravi.', 'private_feedback' => 'Le portail ferme mal.'])
            ->assertCreated()
            ->assertJsonPath('data.reviewer_type', 'user')
            ->assertJsonPath('data.overall_rating', '4.5')
            ->assertJsonPath('data.is_published', false)
            ->assertJsonPath('data.activity.id', $booking->activity_id);

        $review = Review::query()->sole();
        expect($review->activity_id)->toBe($booking->activity_id);
        Notification::assertSentTo([$booking->organization->owner, $manager], AppNotification::class, fn (AppNotification $notification, array $channels, User $user): bool => $notification->toUserDatabase($user)['type'] === NotificationTypeEnum::REVIEW_RECEIVED->value);

        // Caché : l'équipe ne le lit pas encore.
        $this->withHeaders(asUser($manager))->getJson("/api/reviews/{$review->id}")->assertNotFound();
        $this->getJson("/api/activities/{$booking->activity_id}/reviews")->assertJsonCount(0, 'data');

        $this->postJson("/api/bookings/{$booking->id}/reviews", ['overall_rating' => 5, 'comment' => 'Client ponctuel.'])
            ->assertCreated()
            ->assertJsonPath('data.reviewer_type', 'activity')
            ->assertJsonPath('data.reviewer', null);

        // Les deux sont donnés : les deux sont publiés.
        expect(Review::query()->where('is_published', true)->count())->toBe(2);
        $this->getJson("/api/reviews/{$review->id}")
            ->assertOk()
            ->assertJsonPath('data.is_published', true)
            ->assertJsonPath('data.private_feedback', 'Le portail ferme mal.');
    });

    it('takes one review per side', function () {
        $booking = completedStay();

        $this->withHeaders(asUser($booking->user))->postJson("/api/bookings/{$booking->id}/reviews", ['overall_rating' => 4])->assertCreated();
        $this->postJson("/api/bookings/{$booking->id}/reviews", ['overall_rating' => 2])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['booking' => __('reviews.errors.already_reviewed')]);
    });

    it('waits for the end of the booking, and closes after the window', function () {
        $ongoing = Booking::factory()->confirmed()->status(BookingStatusEnum::IN_PROGRESS)->create();
        $old = Booking::factory()->confirmed()->status(BookingStatusEnum::COMPLETED)->between(today()->subDays(20)->toDateString(), today()->subDays(15)->toDateString())->create();

        $this->withHeaders(asUser($ongoing->user))
            ->postJson("/api/bookings/{$ongoing->id}/reviews", ['overall_rating' => 4])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['booking' => __('reviews.errors.not_completed')]);
        $this->withHeaders(asUser($old->user))
            ->postJson("/api/bookings/{$old->id}/reviews", ['overall_rating' => 4])
            ->assertJsonValidationErrors('booking');
    });

    it('rates from 1 to 5 by half points', function () {
        $booking = completedStay();

        $this->withHeaders(asUser($booking->user))
            ->postJson("/api/bookings/{$booking->id}/reviews", ['overall_rating' => 4.3])
            ->assertJsonValidationErrors('overall_rating');
        $this->postJson("/api/bookings/{$booking->id}/reviews", ['overall_rating' => 6])
            ->assertJsonValidationErrors('overall_rating');
    });

    it('is not open to others', function () {
        $booking = completedStay();

        $this->withHeaders(asUser(User::factory()->create()))
            ->postJson("/api/bookings/{$booking->id}/reviews", ['overall_rating' => 4])
            ->assertNotFound();
        $this->withHeaders(asUser(memberOf($booking->organization, OrganizationRoleEnum::EMPLOYEE, $booking->activity_id)))
            ->postJson("/api/bookings/{$booking->id}/reviews", ['overall_rating' => 4])
            ->assertForbidden();
    });
});

describe('reading', function () {
    it('lists the published reviews of an activity, with its rating, to anyone', function () {
        $activity = completedStay()->activity;
        Review::factory()->for(completedStay($activity))->rating('5.0')->published()->create(['published_at' => now()->subDay()]);
        $latest = Review::factory()->for(completedStay($activity))->rating('3.5')->published()->create(['private_feedback' => 'Un peu bruyant.']);
        Review::factory()->for(completedStay($activity))->rating('1.0')->create();

        $this->getJson("/api/activities/{$activity->id}/reviews")
            ->assertOk()
            ->assertJsonPath('data.*.overall_rating', ['3.5', '5.0'])
            ->assertJsonPath('data.0.id', $latest->id)
            ->assertJsonMissingPath('data.0.private_feedback')
            ->assertJsonPath('rating.average', 4.3)
            ->assertJsonPath('rating.count', 2)
            ->assertJsonPath('rating.distribution', ['5' => 1, '4' => 1, '3' => 0, '2' => 0, '1' => 0]);

        $this->withHeaders(asUser(memberOf($activity->organization, OrganizationRoleEnum::EMPLOYEE, $activity->id)))
            ->getJson("/api/activities/{$activity->id}/reviews")
            ->assertJsonPath('data.0.private_feedback', 'Un peu bruyant.');
        $this->getJson("/api/activities/{$activity->id}")->assertJsonPath('data.rating', ['average' => 4.3, 'count' => 2]);
    });

    it('shows the reviews a client gave and received', function () {
        $booking = completedStay();
        $owner = $booking->organization->owner;
        $given = Review::factory()->for($booking)->create();
        $received = Review::factory()->for($booking)->aboutClient($owner->id)->published()->create(['private_feedback' => 'Prévoir la laisse.']);
        // Encore caché : il n'apparaît pas.
        $other = Booking::factory()->confirmed()->status(BookingStatusEnum::COMPLETED)->create(['user_id' => $booking->user_id]);
        Review::factory()->for($other)->aboutClient($other->organization->owner_id)->create();

        $this->withHeaders(asUser($booking->user))
            ->getJson('/api/user/reviews/given')
            ->assertJsonPath('data.*.id', [$given->id]);
        $this->getJson('/api/user/reviews/received')
            ->assertJsonPath('data.*.id', [$received->id])
            ->assertJsonPath('data.0.private_feedback', 'Prévoir la laisse.')
            ->assertJsonPath('rating.count', 1);

        $this->withHeaders(asUser(User::factory()->create()))
            ->getJson("/api/users/{$booking->user_id}/reviews")
            ->assertJsonPath('data.*.id', [$received->id])
            ->assertJsonMissingPath('data.0.private_feedback');
    });
});

it('puts the best rated activities in their own section of the search', function () {
    $rated = function (string $rating, int $reviews): Activity {
        $activity = completedStay()->activity;

        foreach (range(1, $reviews) as $_) {
            Review::factory()->for(completedStay($activity))->rating($rating)->published()->create();
        }

        return $activity;
    };
    $activities = [$rated('4.0', 3), $rated('5.0', 3), $rated('4.5', 3)];
    // Deux avis seulement : pas assez pour la section.
    $rated('5.0', 2);

    $this->getJson('/api/explore/activities/sections/top_rated')
        ->assertOk()
        ->assertJsonPath('data.*.id', [$activities[1]->id, $activities[2]->id, $activities[0]->id])
        ->assertJsonPath('data.0.rating', ['average' => 5, 'count' => 3]);
});
