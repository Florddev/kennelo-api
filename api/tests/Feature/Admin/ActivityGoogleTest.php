<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\User;
use Illuminate\Support\Facades\Http;

it('searches google for an activity and returns a candidate', function () {
    $admin = adminUser();
    $activity = Activity::factory()->create(['name' => 'Pension Test']);

    Http::fake([
        'api.apify.com/*' => Http::response([
            [
                'title' => 'Pension Test Lyon',
                'address' => '10 Rue de Test, 69003 Lyon',
                'city' => 'Lyon',
                'postalCode' => '69003',
                'countryCode' => 'FR',
                'location' => ['lat' => 45.75, 'lng' => 4.85],
                'phoneUnformatted' => '+33612345678',
                'website' => 'https://pension-test.fr',
                'totalScore' => 4.7,
                'reviewsCount' => 128,
                'placeId' => 'ChIJgoogle123',
                'categoryName' => 'Pension pour chiens',
                'categories' => ['Pension pour chiens'],
            ],
        ]),
    ]);

    $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/activities/{$activity->id}/google/search")
        ->assertOk()
        ->assertJsonPath('data.google_place_id', 'ChIJgoogle123')
        ->assertJsonPath('data.google_rating', 4.7)
        ->assertJsonPath('data.google_reviews_count', 128)
        ->assertJsonStructure(['data' => ['google_place_id', 'name', 'address', 'google_maps_url']]);
});

it('returns null candidate when google finds nothing', function () {
    $admin = adminUser();
    $activity = Activity::factory()->create();

    Http::fake(['api.apify.com/*' => Http::response([])]);

    $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/activities/{$activity->id}/google/search")
        ->assertOk()
        ->assertJsonPath('data', null);
});

it('links an activity to a google place manually', function () {
    $admin = adminUser();
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/activities/{$activity->id}/google", [
            'google_place_id' => 'ChIJmanual456',
            'google_rating' => 4.2,
            'google_reviews_count' => 40,
        ])
        ->assertOk()
        ->assertJsonPath('data.google_place_id', 'ChIJmanual456')
        ->assertJsonPath('data.is_google_linked', true);

    $fresh = $activity->fresh();
    expect($fresh->google_place_id)->toBe('ChIJmanual456');
    expect($fresh->google_maps_url)->toContain('place_id:ChIJmanual456');
    expect($fresh->google_synced_at)->not->toBeNull();
});

it('requires a place id to link', function () {
    $admin = adminUser();
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/activities/{$activity->id}/google", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['google_place_id']);
});

it('unlinks an activity from google', function () {
    $admin = adminUser();
    $activity = Activity::factory()->create([
        'google_place_id' => 'ChIJlinked',
        'google_rating' => 4.5,
        'google_synced_at' => now(),
    ]);

    $this->withHeaders(asUser($admin))
        ->deleteJson("/api/admin/activities/{$activity->id}/google")
        ->assertOk()
        ->assertJsonPath('data.is_google_linked', false);

    expect($activity->fresh()->google_place_id)->toBeNull();
});

it('forbids non-admin from google linking', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser($user))
        ->postJson("/api/admin/activities/{$activity->id}/google/search")
        ->assertForbidden();

    $this->withHeaders(asUser($user))
        ->postJson("/api/admin/activities/{$activity->id}/google", ['google_place_id' => 'x'])
        ->assertForbidden();
});
