<?php

declare(strict_types=1);

use App\Models\AnimalType;

use function Pest\Laravel\getJson;

it('accepts a search request with no parameters', function () {
    getJson('/api/explore/search')
        ->assertOk()
        ->assertJsonPath('status', 'success');
});

it('accepts a search combining a date range and an animal count', function () {
    AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);

    getJson('/api/explore/search?'.http_build_query([
        'date_from' => now()->addDays(5)->format('Y-m-d'),
        'date_to' => now()->addDays(8)->format('Y-m-d'),
        'dog' => 1,
    ]))->assertOk()
        ->assertJsonPath('status', 'success');
});

it('accepts a search sorted by price', function () {
    getJson('/api/explore/search?sort=price')
        ->assertOk()
        ->assertJsonPath('status', 'success');
});

it('accepts valid search filters', function () {
    getJson('/api/explore/search?'.http_build_query([
        'location' => 'Paris',
        'host_type' => 'pro',
        'min_rating' => 4,
        'max_price' => 50,
        'sort' => 'rating',
        'page' => 1,
    ]))->assertOk();
});

it('rejects an invalid host_type', function () {
    getJson('/api/explore/search?host_type=invalid')
        ->assertStatus(422)
        ->assertJsonValidationErrors('host_type');
});

it('rejects an invalid sort value', function () {
    getJson('/api/explore/search?sort=cheapest')
        ->assertStatus(422)
        ->assertJsonValidationErrors('sort');
});

it('rejects a min_rating out of range', function () {
    getJson('/api/explore/search?min_rating=9')
        ->assertStatus(422)
        ->assertJsonValidationErrors('min_rating');
});

it('rejects a date_to before date_from', function () {
    getJson('/api/explore/search?'.http_build_query([
        'date_from' => '2026-07-10',
        'date_to' => '2026-07-01',
    ]))->assertStatus(422)
        ->assertJsonValidationErrors('date_to');
});
