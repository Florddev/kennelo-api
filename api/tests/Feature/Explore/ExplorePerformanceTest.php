<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\Address;
use App\Models\User;
use App\Services\Explore\ExploreService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(fn () => Cache::flush());

function visibleActivity(?User $manager = null): Activity
{
    $manager ??= User::factory()->create([
        'stripe_charges_enabled' => true,
        'email_verified_at' => now(),
    ]);

    $address = Address::factory()->create(['latitude' => 48.85, 'longitude' => 2.35]);

    return Activity::factory()->create([
        'manager_id' => $manager->id,
        'address_id' => $address->id,
        'is_active' => true,
    ]);
}

it('caches the explore sections between two calls', function () {
    Cache::flush();
    collect(range(1, 4))->each(fn () => visibleActivity());

    $service = app(ExploreService::class);

    $service->getSections(48.85, 2.35);

    DB::enableQueryLog();
    $service->getSections(48.85, 2.35);
    $secondCallQueries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($secondCallQueries)->toBe(0);
});

it('does not leak favorites across users through the shared cache', function () {
    Cache::flush();
    $activities = collect(range(1, 4))->map(fn () => visibleActivity());

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    DB::table('favorites')->insert([
        'user_id' => $userA->id,
        'activity_id' => $activities->first()->id,
    ]);

    $service = app(ExploreService::class);
    $favoriteId = $activities->first()->id;

    $sectionsA = collect($service->getSections(45.5, 4.5, $userA))->flatMap(fn ($s) => $s['activities']);
    $favoritedForA = $sectionsA->firstWhere('id', $favoriteId)?->getAttribute('is_favorited');

    $sectionsB = collect($service->getSections(45.5, 4.5, $userB))->flatMap(fn ($s) => $s['activities']);
    $favoritedForB = $sectionsB->firstWhere('id', $favoriteId)?->getAttribute('is_favorited');

    expect($favoritedForA)->toBeTrue();
    expect($favoritedForB)->toBeFalse();
});
