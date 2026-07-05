<?php

declare(strict_types=1);

use App\Models\Prospect;
use App\Models\User;

it('returns prospects as a geojson feature collection', function () {
    $admin = adminUser();
    Prospect::factory()->count(2)->create(['latitude' => 45.75, 'longitude' => 4.85]);
    Prospect::factory()->create(['latitude' => null, 'longitude' => null]);

    $this->withHeaders(asUser($admin))
        ->getJson('/api/admin/prospects/map')
        ->assertOk()
        ->assertJsonPath('data.type', 'FeatureCollection')
        ->assertJsonCount(2, 'data.features')
        ->assertJsonPath('data.features.0.geometry.type', 'Point')
        ->assertJsonStructure([
            'data' => [
                'type',
                'features' => [
                    ['type', 'geometry' => ['type', 'coordinates'], 'properties' => ['id', 'name', 'status', 'is_registered']],
                ],
            ],
        ]);
});

it('filters map prospects by status', function () {
    $admin = adminUser();
    Prospect::factory()->create(['latitude' => 45.75, 'longitude' => 4.85, 'status' => 'inscrit']);
    Prospect::factory()->create(['latitude' => 45.76, 'longitude' => 4.86, 'status' => 'non_contacte']);

    $this->withHeaders(asUser($admin))
        ->getJson('/api/admin/prospects/map?status=inscrit')
        ->assertOk()
        ->assertJsonCount(1, 'data.features');
});

it('forbids non-admin from the map endpoint', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/admin/prospects/map')
        ->assertForbidden();
});
