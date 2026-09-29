<?php

declare(strict_types=1);

use App\Models\Activity;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

/**
 * Activité réservable d'une entreprise en offre gratuite (5 photos), qui en a gardé 7 d'une offre supérieure.
 */
function activityWithSevenPhotos(): Activity
{
    $activity = Activity::factory()->bookable()->create();

    foreach (range(1, 7) as $i) {
        $activity->addMedia(UploadedFile::fake()->image("photo-{$i}.jpg"))->toMediaCollection('images');
    }

    return $activity;
}

it('adds photos to an activity', function () {
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser($activity->organization->owner))
        ->post("/api/activities/{$activity->id}/images", [
            'images' => [UploadedFile::fake()->image('salon.jpg'), UploadedFile::fake()->image('bac.png')],
        ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonCount(2);

    expect($activity->getMedia('images'))->toHaveCount(2);
});

it('stops at the photo quota of the plan', function () {
    $activity = Activity::factory()->create();
    $headers = asUser($activity->organization->owner);

    $this->withHeaders($headers)
        ->post("/api/activities/{$activity->id}/images", [
            'images' => array_map(fn (int $i): UploadedFile => UploadedFile::fake()->image("photo-{$i}.jpg"), range(1, 4)),
        ], ['Accept' => 'application/json'])
        ->assertCreated();

    $this->withHeaders($headers)
        ->post("/api/activities/{$activity->id}/images", [
            'images' => [UploadedFile::fake()->image('photo-5.jpg'), UploadedFile::fake()->image('photo-6.jpg')],
        ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['plan' => __('plans.limit_reached.photos', ['limit' => 5])]);

    expect($activity->getMedia('images'))->toHaveCount(4);
});

it('deletes a photo of the activity only', function () {
    $activity = Activity::factory()->create();
    $other = Activity::factory()->create();
    $photo = $other->addMedia(UploadedFile::fake()->image('other.jpg'))->toMediaCollection('images');

    $this->withHeaders(asUser($activity->organization->owner))
        ->deleteJson("/api/activities/{$activity->id}/images/{$photo->uuid}")
        ->assertNotFound();

    $this->withHeaders(asUser($other->organization->owner))
        ->deleteJson("/api/activities/{$other->id}/images/{$photo->uuid}")
        ->assertNoContent();

    expect($other->fresh()->getMedia('images'))->toBeEmpty();
});

describe('after a downgrade', function () {
    it('shows the public only the first photos within the quota when the option is on', function () {
        config(['plans.downgrade.soft_disable.photos' => true]);
        $activity = activityWithSevenPhotos();

        $this->getJson("/api/activities/{$activity->id}")
            ->assertOk()
            ->assertJsonCount(5, 'images')
            ->assertJsonPath('images.4.order', 5);

        $this->withHeaders(asUser($activity->organization->owner))
            ->getJson("/api/activities/{$activity->id}")
            ->assertJsonCount(7, 'images');
    });

    it('keeps every photo visible when the option is off', function () {
        $activity = activityWithSevenPhotos();

        $this->getJson("/api/activities/{$activity->id}")->assertJsonCount(7, 'images');
    });
});
