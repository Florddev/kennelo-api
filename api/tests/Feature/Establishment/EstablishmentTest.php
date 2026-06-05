<?php

declare(strict_types=1);

use App\Enums\EstablishmentPermissionEnum;
use App\Models\Establishment;
use App\Models\User;

// ─── index ────────────────────────────────────────────────────────────────────

it('authenticated user can list establishments', function () {
    $user = User::factory()->create();
    Establishment::factory()->count(3)->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/establishments')
        ->assertOk()
        ->assertJsonStructure(['data']);
});

it('unauthenticated user cannot list establishments', function () {
    $this->getJson('/api/establishments')
        ->assertUnauthorized();
});

// ─── show ─────────────────────────────────────────────────────────────────────

it('any authenticated user can view an establishment', function () {
    $user = User::factory()->create();
    $establishment = Establishment::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson("/api/establishments/{$establishment->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $establishment->id);
});

it('returns 404 for unknown establishment', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/establishments/00000000-0000-0000-0000-000000000000')
        ->assertNotFound();
});

// ─── store ────────────────────────────────────────────────────────────────────

it('manager can create an establishment', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->withHeaders(asUser($manager))
        ->postJson('/api/establishments', ['name' => 'Mon Chenil'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Mon Chenil');
});

it('admin can create an establishment', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->withHeaders(asUser($admin))
        ->postJson('/api/establishments', ['name' => 'Chenil Admin'])
        ->assertCreated();
});

it('regular user without role cannot create an establishment', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->postJson('/api/establishments', ['name' => 'Mon Chenil'])
        ->assertForbidden();
});

it('unauthenticated user cannot create an establishment', function () {
    $this->postJson('/api/establishments', ['name' => 'Mon Chenil'])
        ->assertUnauthorized();
});

it('establishment creation requires a name', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->withHeaders(asUser($manager))
        ->postJson('/api/establishments', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

// ─── update ───────────────────────────────────────────────────────────────────

it('manager (owner) can update their establishment', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/establishments/{$establishment->id}", ['name' => 'Nouveau Nom'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Nouveau Nom');
});

it('admin can update any establishment', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $establishment = Establishment::factory()->create();

    $this->withHeaders(asUser($admin))
        ->putJson("/api/establishments/{$establishment->id}", ['name' => 'Modifié par admin'])
        ->assertOk();
});

it('collaborator with UPDATE_ESTABLISHMENT can update', function () {
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    $establishment->collaborators()->attach($collaborator->id);
    $establishment->collaboratorPermissions()->create([
        'user_id' => $collaborator->id,
        'permission' => EstablishmentPermissionEnum::UPDATE_ESTABLISHMENT->value,
    ]);

    $this->withHeaders(asUser($collaborator))
        ->putJson("/api/establishments/{$establishment->id}", ['name' => 'Modifié par collab'])
        ->assertOk();
});

it('random user cannot update an establishment', function () {
    $establishment = Establishment::factory()->create();
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->putJson("/api/establishments/{$establishment->id}", ['name' => 'Hack'])
        ->assertForbidden();
});

it('collaborator without UPDATE_ESTABLISHMENT cannot update', function () {
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    $establishment->collaborators()->attach($collaborator->id);

    $this->withHeaders(asUser($collaborator))
        ->putJson("/api/establishments/{$establishment->id}", ['name' => 'Hack'])
        ->assertForbidden();
});

// ─── destroy ──────────────────────────────────────────────────────────────────

it('manager can delete their establishment', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->deleteJson("/api/establishments/{$establishment->id}")
        ->assertNoContent();

    expect(Establishment::withTrashed()->find($establishment->id)->deleted_at)->not->toBeNull();
});

it('admin can delete any establishment', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $establishment = Establishment::factory()->create();

    $this->withHeaders(asUser($admin))
        ->deleteJson("/api/establishments/{$establishment->id}")
        ->assertNoContent();
});

it('collaborator cannot delete an establishment', function () {
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    $establishment->collaborators()->attach($collaborator->id);

    $this->withHeaders(asUser($collaborator))
        ->deleteJson("/api/establishments/{$establishment->id}")
        ->assertForbidden();
});

it('random user cannot delete an establishment', function () {
    $establishment = Establishment::factory()->create();
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->deleteJson("/api/establishments/{$establishment->id}")
        ->assertForbidden();
});

// ─── syncCollaboratorPermissions ──────────────────────────────────────────────

it('manager can sync permissions for a collaborator', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    $establishment->collaborators()->attach($collaborator->id);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/establishments/{$establishment->id}/collaborators/{$collaborator->id}/permissions", [
            'permissions' => [EstablishmentPermissionEnum::MANAGE_CAPACITIES->value],
        ])
        ->assertOk();
});

it('admin can sync permissions for a collaborator', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    $establishment->collaborators()->attach($collaborator->id);

    $this->withHeaders(asUser($admin))
        ->putJson("/api/establishments/{$establishment->id}/collaborators/{$collaborator->id}/permissions", [
            'permissions' => [EstablishmentPermissionEnum::MANAGE_AVAILABILITIES->value],
        ])
        ->assertOk();
});

it('syncing permissions for a non-collaborator returns 422', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);

    $stranger = User::factory()->create();

    $this->withHeaders(asUser($manager))
        ->putJson("/api/establishments/{$establishment->id}/collaborators/{$stranger->id}/permissions", [
            'permissions' => [EstablishmentPermissionEnum::MANAGE_CAPACITIES->value],
        ])
        ->assertUnprocessable();
});

it('non-manager cannot sync collaborator permissions', function () {
    $manager = User::factory()->create();
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    $establishment->collaborators()->attach($collaborator->id);

    $randomUser = User::factory()->create();

    $this->withHeaders(asUser($randomUser))
        ->putJson("/api/establishments/{$establishment->id}/collaborators/{$collaborator->id}/permissions", [
            'permissions' => [EstablishmentPermissionEnum::MANAGE_CAPACITIES->value],
        ])
        ->assertForbidden();
});

it('syncing with an invalid permission value returns 422', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $establishment = Establishment::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    $establishment->collaborators()->attach($collaborator->id);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/establishments/{$establishment->id}/collaborators/{$collaborator->id}/permissions", [
            'permissions' => ['invalid_permission'],
        ])
        ->assertUnprocessable();
});
