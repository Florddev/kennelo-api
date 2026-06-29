<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\User;

// ─── index ────────────────────────────────────────────────────────────────────

it('authenticated user can list their favorite activities', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create();
    $user->favoriteActivities()->attach($activity->id);

    $this->withHeaders(asUser($user))
        ->getJson('/api/favorites')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $activity->id)
        ->assertJsonPath('data.0.is_favorited', true);
});

it('user only sees their own favorites, not those of others', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $mine = Activity::factory()->create();
    $theirs = Activity::factory()->create();

    $user->favoriteActivities()->attach($mine->id);
    $other->favoriteActivities()->attach($theirs->id);

    $this->withHeaders(asUser($user))
        ->getJson('/api/favorites')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $mine->id);
});

it('favorites list is paginated', function () {
    $user = User::factory()->create();
    $activities = Activity::factory()->count(3)->create();
    $user->favoriteActivities()->attach($activities->pluck('id')->all());

    $this->withHeaders(asUser($user))
        ->getJson('/api/favorites')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta', 'links']);
});

it('unauthenticated user cannot list favorites', function () {
    $this->getJson('/api/favorites')
        ->assertUnauthorized();
});

// ─── store ────────────────────────────────────────────────────────────────────

it('authenticated user can add an activity to favorites', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser($user))
        ->postJson('/api/favorites', ['activity_id' => $activity->id])
        ->assertCreated()
        ->assertJsonPath('data.id', $activity->id)
        ->assertJsonPath('data.is_favorited', true);

    expect($user->favoriteActivities()->count())->toBe(1);
});

it('adding the same activity twice is idempotent', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser($user))
        ->postJson('/api/favorites', ['activity_id' => $activity->id])
        ->assertCreated();

    $this->withHeaders(asUser($user))
        ->postJson('/api/favorites', ['activity_id' => $activity->id])
        ->assertCreated();

    expect($user->favoriteActivities()->count())->toBe(1);
});

it('adding a favorite requires an activity_id', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->postJson('/api/favorites', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['activity_id']);
});

it('adding a favorite requires an existing activity', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->postJson('/api/favorites', ['activity_id' => '00000000-0000-0000-0000-000000000000'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['activity_id']);
});

it('unauthenticated user cannot add a favorite', function () {
    $activity = Activity::factory()->create();

    $this->postJson('/api/favorites', ['activity_id' => $activity->id])
        ->assertUnauthorized();
});

// ─── destroy ──────────────────────────────────────────────────────────────────

it('authenticated user can remove an activity from favorites', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create();
    $user->favoriteActivities()->attach($activity->id);

    $this->withHeaders(asUser($user))
        ->deleteJson("/api/favorites/{$activity->id}")
        ->assertNoContent();

    expect($user->favoriteActivities()->count())->toBe(0);
});

it('removing a favorite only detaches the current user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $activity = Activity::factory()->create();

    $user->favoriteActivities()->attach($activity->id);
    $other->favoriteActivities()->attach($activity->id);

    $this->withHeaders(asUser($user))
        ->deleteJson("/api/favorites/{$activity->id}")
        ->assertNoContent();

    expect($user->favoriteActivities()->count())->toBe(0)
        ->and($other->favoriteActivities()->count())->toBe(1);
});

it('unauthenticated user cannot remove a favorite', function () {
    $activity = Activity::factory()->create();

    $this->deleteJson("/api/favorites/{$activity->id}")
        ->assertUnauthorized();
});

// ─── is_favorited ───────────────────────────────────────────────────────────────

it('exposes is_favorited true on an activity the user favorited', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create(['is_active' => true]);
    $user->favoriteActivities()->attach($activity->id);

    $this->withHeaders(asUser($user))
        ->getJson("/api/activities/{$activity->id}")
        ->assertOk()
        ->assertJsonPath('data.is_favorited', true);
});

it('exposes is_favorited false on an activity the user did not favorite', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create(['is_active' => true]);

    $this->withHeaders(asUser($user))
        ->getJson("/api/activities/{$activity->id}")
        ->assertOk()
        ->assertJsonPath('data.is_favorited', false);
});
