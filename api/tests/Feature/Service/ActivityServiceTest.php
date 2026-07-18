<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\AnimalType;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Str;

function makeServiceFixtures(): array
{
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $animalType = AnimalType::create(['code' => 'dog_'.Str::random(8), 'name' => 'Chien', 'category' => 'mammals']);

    return [$manager, $activity, $animalType];
}

it('manager can create a service', function () {
    [$manager, $activity, $animalType] = makeServiceFixtures();

    $this->withHeaders(asUser($manager))
        ->postJson("/api/activities/{$activity->id}/services", [
            'name' => 'Toilettage',
            'price' => 25.00,
            'animal_type_id' => $animalType->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Toilettage');

    expect(Service::where('activity_id', $activity->id)->where('name', 'Toilettage')->exists())->toBeTrue();
});

it('non-manager cannot create a service', function () {
    [$manager, $activity, $animalType] = makeServiceFixtures();
    $other = User::factory()->create();

    $this->withHeaders(asUser($other))
        ->postJson("/api/activities/{$activity->id}/services", [
            'name' => 'Toilettage',
            'price' => 25.00,
            'animal_type_id' => $animalType->id,
        ])
        ->assertForbidden();
});

it('manager can list services', function () {
    [$manager, $activity, $animalType] = makeServiceFixtures();
    Service::create(['activity_id' => $activity->id, 'animal_type_id' => $animalType->id, 'name' => 'A', 'price' => 5]);
    Service::create(['activity_id' => $activity->id, 'animal_type_id' => $animalType->id, 'name' => 'B', 'price' => 5]);

    $this->withHeaders(asUser($manager))
        ->getJson("/api/activities/{$activity->id}/services")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('manager can delete a service', function () {
    [$manager, $activity, $animalType] = makeServiceFixtures();
    $service = Service::create(['activity_id' => $activity->id, 'animal_type_id' => $animalType->id, 'name' => 'A', 'price' => 5]);

    $this->withHeaders(asUser($manager))
        ->deleteJson("/api/activities/{$activity->id}/services/{$service->id}")
        ->assertOk();

    expect(Service::find($service->id))->toBeNull();
});

it('manager cannot delete a service from another activity', function () {
    [$manager, $activity, $animalType] = makeServiceFixtures();
    $otherActivity = Activity::factory()->create(['manager_id' => $manager->id]);
    $service = Service::create(['activity_id' => $otherActivity->id, 'animal_type_id' => $animalType->id, 'name' => 'A', 'price' => 5]);

    $this->withHeaders(asUser($manager))
        ->deleteJson("/api/activities/{$activity->id}/services/{$service->id}")
        ->assertNotFound();
});
