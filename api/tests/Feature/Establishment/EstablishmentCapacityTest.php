<?php

declare(strict_types=1);

use App\Enums\EstablishmentPermissionEnum;
use App\Models\AnimalType;
use App\Models\Establishment;
use App\Models\EstablishmentCapacity;
use App\Models\User;

// ─── index ────────────────────────────────────────────────────────────────────

it('manager can list capacities', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->getJson("/api/establishments/{$establishment->id}/capacities")
        ->assertOk()
        ->assertJsonStructure(['data']);
});

it('admin can list capacities', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $establishment = Establishment::factory()->create();

    $this->withHeaders(asUser($admin))
        ->getJson("/api/establishments/{$establishment->id}/capacities")
        ->assertOk();
});

it('collaborator can list capacities without specific permission', function () {
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    $establishment->collaborators()->attach($collaborator->id);

    $this->withHeaders(asUser($collaborator))
        ->getJson("/api/establishments/{$establishment->id}/capacities")
        ->assertOk();
});

it('random user cannot list capacities', function () {
    $establishment = Establishment::factory()->create();
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson("/api/establishments/{$establishment->id}/capacities")
        ->assertForbidden();
});

// ─── store ────────────────────────────────────────────────────────────────────

it('manager can create a capacity', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/establishments/{$establishment->id}/capacities", [
            'animal_type_id' => $animalType->id,
            'max_capacity' => 10,
            'price_per_night' => 25.00,
        ])
        ->assertCreated()
        ->assertJsonPath('data.max_capacity', 10)
        ->assertJsonPath('data.animal_type.code', 'dog');
});

it('admin can create a capacity', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $establishment = Establishment::factory()->create();
    $animalType = AnimalType::create(['code' => 'cat', 'name' => 'Chat', 'category' => 'mammals']);

    $this->withHeaders(asUser($admin))
        ->postJson("/api/establishments/{$establishment->id}/capacities", [
            'animal_type_id' => $animalType->id,
            'max_capacity' => 5,
            'price_per_night' => 15.00,
        ])
        ->assertCreated();
});

it('collaborator with MANAGE_CAPACITIES can create a capacity', function () {
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $animalType = AnimalType::create(['code' => 'bird', 'name' => 'Oiseau', 'category' => 'birds']);

    $collaborator = User::factory()->create();
    $establishment->collaborators()->attach($collaborator->id);
    $establishment->collaboratorPermissions()->create([
        'user_id' => $collaborator->id,
        'permission' => EstablishmentPermissionEnum::MANAGE_CAPACITIES->value,
    ]);

    $this->withHeaders(asUser($collaborator))
        ->postJson("/api/establishments/{$establishment->id}/capacities", [
            'animal_type_id' => $animalType->id,
            'max_capacity' => 3,
            'price_per_night' => 10.00,
        ])
        ->assertCreated();
});

it('collaborator without MANAGE_CAPACITIES cannot create a capacity', function () {
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);

    $collaborator = User::factory()->create();
    $establishment->collaborators()->attach($collaborator->id);

    $this->withHeaders(asUser($collaborator))
        ->postJson("/api/establishments/{$establishment->id}/capacities", [
            'animal_type_id' => $animalType->id,
            'max_capacity' => 5,
            'price_per_night' => 20.00,
        ])
        ->assertForbidden();
});

it('random user cannot create a capacity', function () {
    $establishment = Establishment::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->postJson("/api/establishments/{$establishment->id}/capacities", [
            'animal_type_id' => $animalType->id,
            'max_capacity' => 5,
            'price_per_night' => 20.00,
        ])
        ->assertForbidden();
});

it('cannot create a duplicate capacity for the same animal type in the same establishment', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);

    EstablishmentCapacity::create([
        'establishment_id' => $establishment->id,
        'animal_type_id' => $animalType->id,
        'max_capacity' => 5,
        'price_per_night' => 20.00,
    ]);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/establishments/{$establishment->id}/capacities", [
            'animal_type_id' => $animalType->id,
            'max_capacity' => 10,
            'price_per_night' => 30.00,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['animal_type_id']);
});

it('same animal type can be added to different establishments', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $establishment1 = Establishment::factory()->create(['manager_id' => $manager->id]);
    $establishment2 = Establishment::factory()->create(['manager_id' => $manager->id]);
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);

    EstablishmentCapacity::create([
        'establishment_id' => $establishment1->id,
        'animal_type_id' => $animalType->id,
        'max_capacity' => 5,
        'price_per_night' => 20.00,
    ]);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/establishments/{$establishment2->id}/capacities", [
            'animal_type_id' => $animalType->id,
            'max_capacity' => 8,
            'price_per_night' => 25.00,
        ])
        ->assertCreated();
});

it('capacity max_capacity must be at least 1', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/establishments/{$establishment->id}/capacities", [
            'animal_type_id' => $animalType->id,
            'max_capacity' => 0,
            'price_per_night' => 20.00,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['max_capacity']);
});

it('capacity price_per_night cannot be negative', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/establishments/{$establishment->id}/capacities", [
            'animal_type_id' => $animalType->id,
            'max_capacity' => 5,
            'price_per_night' => -1,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['price_per_night']);
});

// ─── update ───────────────────────────────────────────────────────────────────

it('manager can update a capacity', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);

    $capacity = EstablishmentCapacity::create([
        'establishment_id' => $establishment->id,
        'animal_type_id' => $animalType->id,
        'max_capacity' => 5,
        'price_per_night' => 20.00,
    ]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/establishments/{$establishment->id}/capacities/{$capacity->id}", [
            'max_capacity' => 15,
        ])
        ->assertOk()
        ->assertJsonPath('data.max_capacity', 15);
});

it('collaborator with MANAGE_CAPACITIES can update a capacity', function () {
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);

    $capacity = EstablishmentCapacity::create([
        'establishment_id' => $establishment->id,
        'animal_type_id' => $animalType->id,
        'max_capacity' => 5,
        'price_per_night' => 20.00,
    ]);

    $collaborator = User::factory()->create();
    $establishment->collaborators()->attach($collaborator->id);
    $establishment->collaboratorPermissions()->create([
        'user_id' => $collaborator->id,
        'permission' => EstablishmentPermissionEnum::MANAGE_CAPACITIES->value,
    ]);

    $this->withHeaders(asUser($collaborator))
        ->putJson("/api/establishments/{$establishment->id}/capacities/{$capacity->id}", [
            'max_capacity' => 8,
        ])
        ->assertOk();
});

it('updating a capacity from another establishment returns 404', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $otherEstablishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);

    $capacity = EstablishmentCapacity::create([
        'establishment_id' => $otherEstablishment->id,
        'animal_type_id' => $animalType->id,
        'max_capacity' => 5,
        'price_per_night' => 20.00,
    ]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/establishments/{$establishment->id}/capacities/{$capacity->id}", [
            'max_capacity' => 10,
        ])
        ->assertNotFound();
});

it('random user cannot update a capacity', function () {
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);

    $capacity = EstablishmentCapacity::create([
        'establishment_id' => $establishment->id,
        'animal_type_id' => $animalType->id,
        'max_capacity' => 5,
        'price_per_night' => 20.00,
    ]);

    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->putJson("/api/establishments/{$establishment->id}/capacities/{$capacity->id}", [
            'max_capacity' => 10,
        ])
        ->assertForbidden();
});

// ─── destroy ──────────────────────────────────────────────────────────────────

it('manager can delete a capacity', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);

    $capacity = EstablishmentCapacity::create([
        'establishment_id' => $establishment->id,
        'animal_type_id' => $animalType->id,
        'max_capacity' => 5,
        'price_per_night' => 20.00,
    ]);

    $this->withHeaders(asUser($manager))
        ->deleteJson("/api/establishments/{$establishment->id}/capacities/{$capacity->id}")
        ->assertNoContent();

    expect(EstablishmentCapacity::find($capacity->id))->toBeNull();
});

it('deleting a capacity from another establishment returns 404', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $otherEstablishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);

    $capacity = EstablishmentCapacity::create([
        'establishment_id' => $otherEstablishment->id,
        'animal_type_id' => $animalType->id,
        'max_capacity' => 5,
        'price_per_night' => 20.00,
    ]);

    $this->withHeaders(asUser($manager))
        ->deleteJson("/api/establishments/{$establishment->id}/capacities/{$capacity->id}")
        ->assertNotFound();
});

it('random user cannot delete a capacity', function () {
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);

    $capacity = EstablishmentCapacity::create([
        'establishment_id' => $establishment->id,
        'animal_type_id' => $animalType->id,
        'max_capacity' => 5,
        'price_per_night' => 20.00,
    ]);

    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->deleteJson("/api/establishments/{$establishment->id}/capacities/{$capacity->id}")
        ->assertForbidden();
});
