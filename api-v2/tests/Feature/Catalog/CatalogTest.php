<?php

declare(strict_types=1);

use App\Enums\OrganizationRoleEnum;
use App\Models\AnimalBreed;
use App\Models\AnimalType;
use App\Models\Organization;
use App\Models\Service;

describe('services', function () {
    it('creates a service in the catalog of the company', function () {
        $organization = Organization::factory()->create();

        $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::MANAGER)))
            ->postJson("/api/organizations/{$organization->id}/services", ['name' => 'Toilettage complet', 'requires_scheduling' => true])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Toilettage complet')
            ->assertJsonPath('data.is_package', false)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.prices', []);
    });

    it('lets the team read the catalog but not change it without the permission', function () {
        $organization = Organization::factory()->create();
        Service::factory()->for($organization)->create();
        $accountant = memberOf($organization, OrganizationRoleEnum::ACCOUNTANT);

        $this->withHeaders(asUser($accountant))
            ->getJson("/api/organizations/{$organization->id}/services")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withHeaders(asUser($accountant))
            ->postJson("/api/organizations/{$organization->id}/services", ['name' => 'Bain'])
            ->assertForbidden();
    });

    it('does not turn a service into a package afterwards', function () {
        $service = Service::factory()->create();

        $this->withHeaders(asUser($service->organization->owner))
            ->patchJson("/api/organizations/{$service->organization_id}/services/{$service->id}", ['is_package' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_package']);
    });

    it('returns 404 for a service of another company', function () {
        $organization = Organization::factory()->create();
        $foreign = Service::factory()->create();

        $this->withHeaders(asUser($organization->owner))
            ->patchJson("/api/organizations/{$organization->id}/services/{$foreign->id}", ['name' => 'Bain'])
            ->assertNotFound();
    });

    it('deletes a service logically', function () {
        $service = Service::factory()->create();

        $this->withHeaders(asUser($service->organization->owner))
            ->deleteJson("/api/organizations/{$service->organization_id}/services/{$service->id}")
            ->assertNoContent();

        expect(Service::withTrashed()->findOrFail($service->id)->trashed())->toBeTrue();
    });

    it('refuses to delete a service that a package contains', function () {
        $organization = Organization::factory()->create();
        $bath = Service::factory()->for($organization)->create();
        Service::factory()->for($organization)->package()->create()->packageItems()->attach($bath->id);

        $this->withHeaders(asUser($organization->owner))
            ->deleteJson("/api/organizations/{$organization->id}/services/{$bath->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service' => __('catalog.service_in_package')]);
    });
});

describe('price grid', function () {
    it('replaces the whole grid', function () {
        $dog = AnimalType::factory()->dog()->create();
        $poodle = AnimalBreed::factory()->for($dog)->create();
        $service = Service::factory()->create();
        $service->prices()->create(['animal_type_id' => $dog->id, 'price' => '10.00']);

        $this->withHeaders(asUser($service->organization->owner))
            ->putJson("/api/organizations/{$service->organization_id}/services/{$service->id}/prices", ['prices' => [
                ['animal_type_id' => $dog->id, 'price' => 30],
                ['animal_type_id' => $dog->id, 'size_class' => 'large', 'coat_type' => 'long', 'price' => '55.5'],
                ['animal_type_id' => $dog->id, 'animal_breed_id' => $poodle->id, 'price' => '65.00', 'duration_minutes' => 90],
            ]])
            ->assertOk()
            ->assertJsonCount(3, 'data.prices')
            ->assertJsonPath('data.prices.1.price', '55.50');

        expect($service->prices()->count())->toBe(3);
    });

    it('rejects lines that cannot be told apart or combine criteria wrongly', function (array $lines, string $error) {
        $dog = AnimalType::factory()->dog()->create();
        $cat = AnimalType::factory()->cat()->create();
        $poodle = AnimalBreed::factory()->for($dog)->create();
        $service = Service::factory()->create();

        $replace = fn (string $value): string => match ($value) {
            'dog' => $dog->id,
            'cat' => $cat->id,
            'poodle' => $poodle->id,
            default => $value,
        };

        $this->withHeaders(asUser($service->organization->owner))
            ->putJson("/api/organizations/{$service->organization_id}/services/{$service->id}/prices", [
                'prices' => array_map(fn (array $line): array => array_map($replace, $line), $lines),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$error]);
    })->with([
        'duplicate' => [[['animal_type_id' => 'dog', 'price' => '30'], ['animal_type_id' => 'dog', 'price' => '35']], 'prices'],
        'breed and size' => [[['animal_type_id' => 'dog', 'animal_breed_id' => 'poodle', 'size_class' => 'large', 'price' => '30']], 'prices.0.animal_breed_id'],
        'breed of another species' => [[['animal_type_id' => 'cat', 'animal_breed_id' => 'poodle', 'price' => '30']], 'prices.0.animal_breed_id'],
        'coat without size' => [[['animal_type_id' => 'dog', 'coat_type' => 'long', 'price' => '30']], 'prices.0.coat_type'],
        'negative price' => [[['animal_type_id' => 'dog', 'price' => '-1']], 'prices.0.price'],
        'three decimals' => [[['animal_type_id' => 'dog', 'price' => '10.505']], 'prices.0.price'],
    ]);

    it('requires a duration for a service placed in the agenda', function () {
        $dog = AnimalType::factory()->dog()->create();
        $service = Service::factory()->create(['requires_scheduling' => true]);

        $this->withHeaders(asUser($service->organization->owner))
            ->putJson("/api/organizations/{$service->organization_id}/services/{$service->id}/prices", ['prices' => [
                ['animal_type_id' => $dog->id, 'price' => '30'],
            ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['prices.0.duration_minutes']);
    });
});

describe('packages', function () {
    it('replaces the content of a package', function () {
        $organization = Organization::factory()->create();
        $package = Service::factory()->for($organization)->package()->create();
        [$bath, $cut] = Service::factory()->for($organization)->count(2)->create();

        $this->withHeaders(asUser($organization->owner))
            ->putJson("/api/organizations/{$organization->id}/services/{$package->id}/package-items", ['service_ids' => [$bath->id, $cut->id]])
            ->assertOk()
            ->assertJsonCount(2, 'data.package_items');
    });

    it('only contains simple services of the same company', function (Closure $makeItem) {
        $organization = Organization::factory()->create();
        $package = Service::factory()->for($organization)->package()->create();

        $this->withHeaders(asUser($organization->owner))
            ->putJson("/api/organizations/{$organization->id}/services/{$package->id}/package-items", ['service_ids' => [$makeItem($organization)->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service_ids.0' => __('catalog.invalid_package_item')]);
    })->with([
        'another package' => fn (Organization $organization) => Service::factory()->for($organization)->package()->create(),
        'another company' => fn () => Service::factory()->create(),
        'deleted service' => fn (Organization $organization) => tap(Service::factory()->for($organization)->create())->delete(),
    ]);

    it('refuses items for a simple service', function () {
        $organization = Organization::factory()->create();
        [$bath, $cut] = Service::factory()->for($organization)->count(2)->create();

        $this->withHeaders(asUser($organization->owner))
            ->putJson("/api/organizations/{$organization->id}/services/{$bath->id}/package-items", ['service_ids' => [$cut->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service_ids' => __('catalog.not_a_package')]);
    });
});
