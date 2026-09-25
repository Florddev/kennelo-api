<?php

declare(strict_types=1);

use App\Enums\OrganizationRoleEnum;
use App\Enums\PetSizeClassEnum;
use App\Enums\ServiceOfferEnum;
use App\Models\Activity;
use App\Models\AnimalBreed;
use App\Models\AnimalType;
use App\Models\Pet;
use App\Models\Profession;
use App\Models\Service;
use App\Models\User;

describe('offer management', function () {
    it('adds a service of the catalog to the offer of the activity', function () {
        $activity = Activity::factory()->create();
        $service = Service::factory()->for($activity->organization)->create();

        $this->withHeaders(asUser($activity->organization->owner))
            ->putJson("/api/activities/{$activity->id}/services/{$service->id}", ['adjustment_percent' => -10])
            ->assertOk()
            ->assertJsonPath('data.offer', [
                'offered_as' => 'standalone',
                'adjustment_percent' => '-10.00',
                'is_included' => false,
                'is_active' => true,
            ]);

        expect($activity->services()->sole()->offer->organization_id)->toBe($activity->organization_id);
    });

    it('replaces the conditions of an existing offer', function () {
        $activity = Activity::factory()->for(Profession::factory()->stay())->create();
        $service = Service::factory()->for($activity->organization)->create();
        $headers = asUser($activity->organization->owner);

        $this->withHeaders($headers)->putJson("/api/activities/{$activity->id}/services/{$service->id}", ['adjustment_percent' => -10]);
        $this->withHeaders($headers)
            ->putJson("/api/activities/{$activity->id}/services/{$service->id}", ['offered_as' => 'stay_option', 'is_included' => true])
            ->assertOk()
            ->assertJsonPath('data.offer.offered_as', 'stay_option')
            ->assertJsonPath('data.offer.adjustment_percent', '0.00')
            ->assertJsonPath('data.offer.is_included', true);

        expect($activity->services()->count())->toBe(1);
    });

    it('lets the manager of the activity offer a service', function () {
        $activity = Activity::factory()->create();
        $service = Service::factory()->for($activity->organization)->create();

        $this->withHeaders(asUser(memberOf($activity->organization, OrganizationRoleEnum::ACTIVITY_MANAGER, $activity->id)))
            ->putJson("/api/activities/{$activity->id}/services/{$service->id}")
            ->assertOk();
    });

    it('returns 404 for a service of another company', function () {
        $activity = Activity::factory()->create();

        $this->withHeaders(asUser($activity->organization->owner))
            ->putJson("/api/activities/{$activity->id}/services/".Service::factory()->create()->id)
            ->assertNotFound();

        expect($activity->services()->exists())->toBeFalse();
    });

    it('sells stay options only in an activity that offers stays', function () {
        $activity = Activity::factory()->create();
        $service = Service::factory()->for($activity->organization)->create();

        $this->withHeaders(asUser($activity->organization->owner))
            ->putJson("/api/activities/{$activity->id}/services/{$service->id}", ['offered_as' => 'stay_option'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['offered_as' => __('catalog.stay_option_requires_stay')]);
    });

    it('includes only a stay option in the price of the stay', function () {
        $activity = Activity::factory()->for(Profession::factory()->stay())->create();
        $service = Service::factory()->for($activity->organization)->create();

        $this->withHeaders(asUser($activity->organization->owner))
            ->putJson("/api/activities/{$activity->id}/services/{$service->id}", ['offered_as' => 'standalone', 'is_included' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_included' => __('catalog.included_requires_stay_option')]);
    });

    it('withdraws a service from the offer and keeps it in the catalog', function () {
        $activity = Activity::factory()->create();
        $service = Service::factory()->for($activity->organization)->create();
        $activity->services()->attach($service->id, ['organization_id' => $activity->organization_id]);

        $this->withHeaders(asUser($activity->organization->owner))
            ->deleteJson("/api/activities/{$activity->id}/services/{$service->id}")
            ->assertNoContent();

        expect($activity->services()->exists())->toBeFalse()
            ->and($service->fresh())->not->toBeNull();
    });
});

/**
 * Salon réservable qui vend un toilettage à -10 % (grille chien, grand chien, caniche) et a retiré le bain de la vente.
 *
 * @return array{0: Activity, 1: AnimalType, 2: AnimalBreed}
 */
function groomingSalon(): array
{
    $dog = AnimalType::factory()->dog()->create();
    $poodle = AnimalBreed::factory()->for($dog)->create();
    $activity = Activity::factory()->bookable()->create();

    $grooming = Service::factory()->for($activity->organization)->create(['name' => 'Toilettage']);
    $grooming->prices()->createMany([
        ['animal_type_id' => $dog->id, 'price' => '30.00', 'duration_minutes' => 60],
        ['animal_type_id' => $dog->id, 'size_class' => PetSizeClassEnum::LARGE, 'price' => '45.00', 'duration_minutes' => 90],
        ['animal_type_id' => $dog->id, 'animal_breed_id' => $poodle->id, 'price' => '65.00', 'duration_minutes' => 120],
    ]);
    $activity->services()->attach($grooming->id, [
        'organization_id' => $activity->organization_id,
        'offered_as' => ServiceOfferEnum::STANDALONE,
        'adjustment_percent' => '-10',
    ]);

    $bath = Service::factory()->for($activity->organization)->create(['name' => 'Bain']);
    $activity->services()->attach($bath->id, ['organization_id' => $activity->organization_id, 'is_active' => false]);

    return [$activity, $dog, $poodle];
}

describe('services of an activity', function () {
    it('shows the grid adjusted by the activity, without the services withdrawn from sale', function () {
        [$activity] = groomingSalon();

        $this->getJson("/api/activities/{$activity->id}/services")
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Toilettage'])
            ->assertJsonPath('data.0.prices.*.price', ['27.00', '40.50', '58.50'])
            ->assertJsonMissingPath('data.0.pet_price');
    });

    it('shows the team every service it offers', function () {
        [$activity] = groomingSalon();

        $this->withHeaders(asUser($activity->organization->owner))
            ->getJson("/api/activities/{$activity->id}/services")
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Bain', 'Toilettage']);
    });

    it('gives the price and the duration for the pet of the client', function (Closure $makePet, ?array $expected) {
        [$activity, $dog, $poodle] = groomingSalon();
        $client = User::factory()->create();
        $pet = $makePet($client, $dog, $poodle);

        $this->withHeaders(asUser($client))
            ->getJson("/api/activities/{$activity->id}/services?pet_id={$pet->id}")
            ->assertOk()
            ->assertJsonPath('data.0.pet_price', $expected);
    })->with([
        'poodle' => [
            fn (User $client, AnimalType $dog, AnimalBreed $poodle) => Pet::factory()->for($client)->for($dog)->create(['animal_breed_id' => $poodle->id]),
            ['price' => '58.50', 'duration_minutes' => 120],
        ],
        'heavy dog' => [
            fn (User $client, AnimalType $dog) => Pet::factory()->for($client)->for($dog)->create(['weight' => '38.00']),
            ['price' => '40.50', 'duration_minutes' => 90],
        ],
        'small dog' => [
            fn (User $client, AnimalType $dog) => Pet::factory()->for($client)->for($dog)->create(['size_class' => PetSizeClassEnum::SMALL]),
            ['price' => '27.00', 'duration_minutes' => 60],
        ],
        'cat' => [
            fn (User $client) => Pet::factory()->for($client)->for(AnimalType::factory()->cat())->create(),
            null,
        ],
    ]);

    it('refuses the pet of someone else', function () {
        [$activity, $dog] = groomingSalon();
        $pet = Pet::factory()->for($dog)->create();

        $this->withHeaders(asUser(User::factory()->create()))
            ->getJson("/api/activities/{$activity->id}/services?pet_id={$pet->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['pet_id']);
    });
});
