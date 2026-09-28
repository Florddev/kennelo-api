<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\AnimalType;
use App\Models\Organization;
use App\Models\Profession;
use App\Models\ProfessionCategory;
use App\Models\User;

// Le client cherche depuis le centre de Lyon.
const LYON = ['lat' => 45.7640, 'lng' => 4.8357];
const VILLEURBANNE = [45.7719, 4.8902];
const VIENNE = [45.5255, 4.8740];
const GRENOBLE = [45.1885, 5.7245];

/**
 * @param  array<string, mixed>  $query
 * @return list<string>
 */
function searchIds(array $query = []): array
{
    return test()->getJson('/api/explore/search?'.http_build_query($query))->assertOk()->json('data.*.id');
}

describe('search', function () {
    it('only returns bookable activities', function () {
        $bookable = Activity::factory()->bookable()->create();
        Activity::factory()->create();
        Activity::factory()->bookable()->create(['is_active' => false]);
        Activity::factory()->bookable()->create()->delete();

        expect(searchIds())->toBe([$bookable->id]);
    });

    it('filters by profession, category and species', function () {
        $dog = AnimalType::factory()->dog()->create();
        $care = ProfessionCategory::factory()->create(['code' => 'care']);
        $grooming = Profession::factory()->for($care, 'category')->create(['code' => 'grooming']);
        $salon = Activity::factory()->bookable()->for($grooming)->forSpecies($dog)->create();
        Activity::factory()->bookable()->forSpecies(AnimalType::factory()->cat()->create())->create();

        expect(searchIds(['profession' => 'grooming']))->toBe([$salon->id])
            ->and(searchIds(['category' => 'care']))->toBe([$salon->id])
            ->and(searchIds(['animal_type' => 'dog']))->toBe([$salon->id]);
    });

    it('separates professionals from individuals', function () {
        $pro = Activity::factory()->bookable()->create();
        $individual = Activity::factory()->approved()->state([
            'organization_id' => Organization::factory()->individual()->verified()->withStripe()->withBillingMandate(),
        ])->create();

        expect(searchIds(['host_type' => 'pro']))->toBe([$pro->id])
            ->and(searchIds(['host_type' => 'individual']))->toBe([$individual->id]);
    });

    it('keeps the activities within the radius, closest first', function () {
        $vienne = Activity::factory()->bookable()->at(...VIENNE)->create();
        $villeurbanne = Activity::factory()->bookable()->at(...VILLEURBANNE)->create();
        Activity::factory()->bookable()->at(...GRENOBLE)->create();

        $response = $this->getJson('/api/explore/search?'.http_build_query(LYON))->assertOk();

        expect($response->json('data.*.id'))->toBe([$villeurbanne->id, $vienne->id])
            ->and($response->json('data.0.distance_km'))->toBeGreaterThan(4.0)->toBeLessThan(5.0)
            ->and(searchIds([...LYON, 'radius' => 10]))->toBe([$villeurbanne->id])
            ->and(searchIds([...LYON, 'radius' => 150]))->toHaveCount(3);
    });

    it('keeps a professional who travels only if their radius covers the client', function () {
        $covers = Activity::factory()->bookable()->atClient(30)->at(...VIENNE)->create();
        Activity::factory()->bookable()->atClient(10)->at(...VIENNE)->create();
        Activity::factory()->bookable()->at(...VIENNE)->create();

        expect(searchIds([...LYON, 'location_mode' => 'at_client']))->toBe([$covers->id])
            ->and(searchIds([...LYON, 'location_mode' => 'at_client', 'radius' => 100]))->toBe([$covers->id]);
    });

    it('finds remote activities regardless of the position', function () {
        $remote = Activity::factory()->bookable()->at(...GRENOBLE)->create(['serves_at_pro' => false, 'serves_remote' => true]);
        Activity::factory()->bookable()->at(...VILLEURBANNE)->create();

        expect(searchIds([...LYON, 'location_mode' => 'remote']))->toBe([$remote->id]);
    });

    it('filters by city or postal code', function () {
        $lyon = Activity::factory()->bookable()->create();
        $lyon->address->update(['city' => 'Lyon', 'postal_code' => '69003']);
        Activity::factory()->bookable()->create()->address->update(['city' => 'Grenoble', 'postal_code' => '38000']);

        expect(searchIds(['location' => 'lyon']))->toBe([$lyon->id])
            ->and(searchIds(['location' => '69']))->toBe([$lyon->id]);
    });

    it('tells a client which activities are in their favorites', function () {
        $activity = Activity::factory()->bookable()->create();
        $client = User::factory()->create();
        $client->favoriteActivities()->attach($activity->id, ['created_at' => now()]);

        $this->withHeaders(asUser($client))
            ->getJson('/api/explore/search')
            ->assertJsonPath('data.0.is_favorited', true);
    });

    it('has no favorite flag for a visitor', function () {
        Activity::factory()->bookable()->create();

        $this->getJson('/api/explore/search')->assertJsonMissingPath('data.0.is_favorited');
    });
});

describe('home page sections', function () {
    it('shows the nearby section only with a position and at least three activities', function () {
        Activity::factory()->bookable()->at(...VILLEURBANNE)->count(3)->create();

        $withPosition = $this->getJson('/api/explore/activities?'.http_build_query(LYON))->assertOk();
        $withoutPosition = $this->getJson('/api/explore/activities')->assertOk();

        expect($withPosition->json('data.*.id'))->toContain('nearby')
            ->and($withPosition->json('data.0.activities'))->toHaveCount(3)
            ->and($withoutPosition->json('data.*.id'))->not->toContain('nearby');
    });

    it('omits a section with fewer than three activities', function () {
        Activity::factory()->bookable()->count(2)->create();

        $this->getJson('/api/explore/activities')->assertOk()->assertJsonCount(0, 'data');
    });

    it('pages through a section', function () {
        Activity::factory()->bookable()->count(12)->create();

        $this->getJson('/api/explore/activities/sections/new_hosts')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10);

        $this->getJson('/api/explore/activities/sections/new_hosts?page=2')->assertJsonCount(2, 'data');
    });

    it('has no nearby page without a position, and no unknown section', function () {
        $this->getJson('/api/explore/activities/sections/nearby')->assertNotFound();
        $this->getJson('/api/explore/activities/sections/unknown')->assertNotFound();
    });
});
