<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('adds a bookable activity to the favorites, once', function () {
    $client = User::factory()->create();
    $activity = Activity::factory()->bookable()->create();
    $headers = asUser($client);

    $this->withHeaders($headers)->postJson("/api/favorites/{$activity->id}")->assertNoContent();
    $this->withHeaders($headers)->postJson("/api/favorites/{$activity->id}")->assertNoContent();

    expect(DB::table('favorites')->where('user_id', $client->id)->count())->toBe(1);
});

it('refuses an activity that cannot be booked', function () {
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser(User::factory()->create()))
        ->postJson("/api/favorites/{$activity->id}")
        ->assertNotFound();
});

it('lists the favorites that can still be booked, latest first', function () {
    $client = User::factory()->create();
    [$older, $newer] = Activity::factory()->bookable()->count(2)->create();
    $paused = Activity::factory()->bookable()->create();
    $client->favoriteActivities()->attach([
        $older->id => ['created_at' => now()->subDay()],
        $newer->id => ['created_at' => now()],
        $paused->id => ['created_at' => now()],
    ]);
    $paused->update(['is_active' => false]);

    $this->withHeaders(asUser($client))
        ->getJson('/api/favorites')
        ->assertOk()
        ->assertJsonPath('data.*.id', [$newer->id, $older->id])
        ->assertJsonPath('data.0.is_favorited', true);
});

it('removes a favorite', function () {
    $client = User::factory()->create();
    $activity = Activity::factory()->bookable()->create();
    $client->favoriteActivities()->attach($activity->id, ['created_at' => now()]);

    $this->withHeaders(asUser($client))->deleteJson("/api/favorites/{$activity->id}")->assertNoContent();

    expect($client->favoriteActivities()->exists())->toBeFalse();
});

it('requires authentication', function () {
    $this->getJson('/api/favorites')->assertUnauthorized();
});
