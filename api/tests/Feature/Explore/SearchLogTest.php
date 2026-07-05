<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\SearchLog;
use App\Models\User;
use App\Services\Explore\SearchLogService;

it('records a search log with the resolved department', function () {
    app(SearchLogService::class)->record(
        ['location' => 'Lyon 69003', 'sort' => 'rating', 'min_rating' => 4],
        45.75,
        4.85,
        12,
        null,
    );

    $log = SearchLog::first();

    expect($log)->not->toBeNull();
    expect($log->department)->toBe('69');
    expect($log->results_count)->toBe(12);
    expect($log->filters)->toMatchArray(['sort' => 'rating', 'min_rating' => 4]);
});

it('resolves corsican departments from postal code', function () {
    app(SearchLogService::class)->record(['location' => 'Ajaccio 20000'], null, null, 3, null);

    expect(SearchLog::first()->department)->toBe('2A');
});

it('records a null department when no postal code is present', function () {
    app(SearchLogService::class)->record(['location' => 'Paris'], null, null, 5, null);

    expect(SearchLog::first()->department)->toBeNull();
});

it('logs a search when hitting the explore search endpoint', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/explore/search?location=Lyon 69003')
        ->assertOk();

    expect(SearchLog::count())->toBeGreaterThanOrEqual(1);
});

it('does not log a search when requesting a page beyond the first', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/explore/search?location=Lyon&page=2')
        ->assertOk();

    expect(SearchLog::count())->toBe(0);
});

it('records the total number of results, not the current page slice', function () {
    $user = User::factory()->create();
    Activity::factory()->count(3)->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/explore/search')
        ->assertOk();

    $log = SearchLog::latest()->first();
    expect($log)->not->toBeNull();
    expect($log->results_count)->toBeGreaterThanOrEqual(0);
});
