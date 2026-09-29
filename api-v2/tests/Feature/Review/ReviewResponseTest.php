<?php

declare(strict_types=1);

use App\Enums\OrganizationRoleEnum;
use App\Models\Review;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;

it('lets the team respond once to a published review of a client', function () {
    Notification::fake();
    $review = Review::factory()->published()->create();
    $manager = memberOf($review->activity->organization, OrganizationRoleEnum::ACTIVITY_MANAGER, $review->activity_id);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/reviews/{$review->id}/response", ['response' => 'Merci, à bientôt Rex !'])
        ->assertCreated()
        ->assertJsonPath('response.response', 'Merci, à bientôt Rex !');
    $this->postJson("/api/reviews/{$review->id}/response", ['response' => 'Encore merci.'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['response' => __('reviews.errors.already_answered')]);

    Notification::assertSentTo($review->reviewer, AppNotification::class);
    $this->getJson("/api/activities/{$review->activity_id}/reviews")->assertJsonPath('data.0.response.response', 'Merci, à bientôt Rex !');
});

it('lets the client respond to the review of the team about them', function () {
    $review = Review::factory()->published()->create();
    $owner = $review->activity->organization->owner;
    $aboutClient = Review::factory()->for($review->booking)->aboutClient($owner->id)->published()->create();

    $this->withHeaders(asUser($review->booking->user))
        ->postJson("/api/reviews/{$aboutClient->id}/response", ['response' => 'Merci pour votre accueil.'])
        ->assertCreated();
    $this->withHeaders(asUser($owner))
        ->postJson("/api/reviews/{$review->id}/response", ['response' => 'Merci !'])
        ->assertCreated();
});

it('keeps the response to the reviewed party, and to published reviews', function () {
    $review = Review::factory()->published()->create();
    $hidden = Review::factory()->create();

    $this->withHeaders(asUser($review->reviewer))
        ->postJson("/api/reviews/{$review->id}/response", ['response' => 'Moi-même'])
        ->assertNotFound();
    $this->withHeaders(asUser(memberOf($review->activity->organization, OrganizationRoleEnum::EMPLOYEE, $review->activity_id)))
        ->postJson("/api/reviews/{$review->id}/response", ['response' => 'Merci'])
        ->assertForbidden();
    $this->withHeaders(asUser($hidden->activity->organization->owner))
        ->postJson("/api/reviews/{$hidden->id}/response", ['response' => 'Merci'])
        ->assertNotFound();
    $this->withHeaders(asUser(User::factory()->create()))
        ->postJson("/api/reviews/{$review->id}/response", ['response' => 'Merci'])
        ->assertNotFound();
});
