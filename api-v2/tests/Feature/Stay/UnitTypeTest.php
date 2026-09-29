<?php

declare(strict_types=1);

use App\Enums\OrganizationRoleEnum;
use App\Models\Activity;
use App\Models\ActivityUnitType;
use App\Models\AnimalType;

it('creates a unit for species of the activity', function () {
    $unitType = dogBoarding();
    $activity = $unitType->activity;
    $dog = $unitType->animalTypes->first();

    $this->withHeaders(asUser($activity->organization->owner))
        ->postJson("/api/activities/{$activity->id}/unit-types", [
            'name' => 'Chambre familiale',
            'quantity' => 3,
            'max_animals_per_unit' => 2,
            'animal_type_ids' => [$dog->id],
        ])
        ->assertCreated()
        ->assertJsonPath('name', 'Chambre familiale')
        ->assertJsonPath('max_animals_per_unit', 2)
        ->assertJsonPath('is_active', true)
        ->assertJsonPath('animal_types.0.id', $dog->id);
});

it('refuses a species the activity does not take in', function () {
    $activity = dogBoarding()->activity;
    $cat = AnimalType::factory()->cat()->create();

    $this->withHeaders(asUser($activity->organization->owner))
        ->postJson("/api/activities/{$activity->id}/unit-types", ['name' => 'Chatterie', 'animal_type_ids' => [$cat->id]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['animal_type_ids.0']);
});

it('refuses units to an activity by appointment', function () {
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser($activity->organization->owner))
        ->postJson("/api/activities/{$activity->id}/unit-types", ['name' => 'Box', 'animal_type_ids' => []])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['activity' => __('booking.stay_only')]);
});

it('shows the public only the active units', function () {
    $unitType = dogBoarding();
    ActivityUnitType::factory()->for($unitType->activity)->create(['name' => 'Ancien box', 'is_active' => false]);

    $this->getJson("/api/activities/{$unitType->activity_id}/unit-types")
        ->assertOk()
        ->assertJsonPath('*.name', ['Box']);
});

it('shows the team every unit', function () {
    $unitType = dogBoarding();
    ActivityUnitType::factory()->for($unitType->activity)->create(['name' => 'Ancien box', 'is_active' => false]);

    $this->withHeaders(asUser($unitType->activity->organization->owner))
        ->getJson("/api/activities/{$unitType->activity_id}/unit-types")
        ->assertJsonCount(2);
});

it('updates and deletes a unit of the activity only', function () {
    $unitType = dogBoarding();
    $other = dogBoarding();
    $owner = $unitType->activity->organization->owner;

    $this->withHeaders(asUser($owner))
        ->patchJson("/api/activities/{$unitType->activity_id}/unit-types/{$unitType->id}", ['quantity' => 6])
        ->assertOk()
        ->assertJsonPath('quantity', 6);

    $this->deleteJson("/api/activities/{$unitType->activity_id}/unit-types/{$other->id}")
        ->assertNotFound();

    $this->deleteJson("/api/activities/{$unitType->activity_id}/unit-types/{$unitType->id}")
        ->assertNoContent();
});

it('is reserved to the managers of the activity', function () {
    $unitType = dogBoarding();
    $employee = memberOf($unitType->activity->organization, OrganizationRoleEnum::EMPLOYEE, $unitType->activity_id);

    $this->withHeaders(asUser($employee))
        ->patchJson("/api/activities/{$unitType->activity_id}/unit-types/{$unitType->id}", ['quantity' => 6])
        ->assertForbidden();
});
