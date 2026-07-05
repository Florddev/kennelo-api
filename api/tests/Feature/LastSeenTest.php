<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

beforeEach(fn () => Cache::flush());

it('updates last_seen_at on an authenticated request', function () {
    $user = User::factory()->create(['last_seen_at' => null]);

    $this->withHeaders(asUser($user))
        ->getJson('/api/user')
        ->assertOk();

    expect($user->fresh()->last_seen_at)->not->toBeNull();
});

it('does not update last_seen_at for guests', function () {
    $this->getJson('/api/explore/activities')->assertOk();

    expect(User::withInactive()->whereNotNull('last_seen_at')->count())->toBe(0);
});

it('throttles last_seen_at updates within the window', function () {
    $user = User::factory()->create(['last_seen_at' => null]);

    $this->withHeaders(asUser($user))->getJson('/api/user')->assertOk();
    $firstSeen = Carbon::parse($user->fresh()->last_seen_at)->timestamp;

    $this->travel(5)->minutes();
    $this->withHeaders(asUser($user))->getJson('/api/user')->assertOk();

    expect(Carbon::parse($user->fresh()->last_seen_at)->timestamp)->toBe($firstSeen);
});
