<?php

declare(strict_types=1);

use App\Enums\ActivityPermissionEnum;
use App\Models\Activity;
use App\Models\User;

// ─── index ────────────────────────────────────────────────────────────────────

it('authenticated user can list activities', function () {
    $user = User::factory()->create();
    Activity::factory()->count(3)->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/activities')
        ->assertOk()
        ->assertJsonStructure(['data']);
});

it('unauthenticated user cannot list activities', function () {
    $this->getJson('/api/activities')
        ->assertUnauthorized();
});

// ─── show ─────────────────────────────────────────────────────────────────────

it('any authenticated user can view an activity', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson("/api/activities/{$activity->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $activity->id);
});

it('returns 404 for unknown activity', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/activities/00000000-0000-0000-0000-000000000000')
        ->assertNotFound();
});

// ─── store ────────────────────────────────────────────────────────────────────

it('manager can create an activity', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->withHeaders(asUser($manager))
        ->postJson('/api/activities', ['name' => 'Mon Chenil'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Mon Chenil');
});

it('admin can create an activity', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->withHeaders(asUser($admin))
        ->postJson('/api/activities', ['name' => 'Chenil Admin'])
        ->assertCreated();
});

it('regular user without role cannot create an activity', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->postJson('/api/activities', ['name' => 'Mon Chenil'])
        ->assertForbidden();
});

it('unauthenticated user cannot create an activity', function () {
    $this->postJson('/api/activities', ['name' => 'Mon Chenil'])
        ->assertUnauthorized();
});

it('activity creation requires a name', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->withHeaders(asUser($manager))
        ->postJson('/api/activities', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

// ─── update ───────────────────────────────────────────────────────────────────

it('manager (owner) can update their activity', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}", ['name' => 'Nouveau Nom'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Nouveau Nom');
});

it('admin can update any activity', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser($admin))
        ->putJson("/api/activities/{$activity->id}", ['name' => 'Modifié par admin'])
        ->assertOk();
});

it('collaborator with UPDATE_ACTIVITY can update', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    attachCollaborator($activity, $collaborator, [ActivityPermissionEnum::UPDATE_ACTIVITY]);

    $this->withHeaders(asUser($collaborator))
        ->putJson("/api/activities/{$activity->id}", ['name' => 'Modifié par collab'])
        ->assertOk();
});

it('random user cannot update an activity', function () {
    $activity = Activity::factory()->create();
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->putJson("/api/activities/{$activity->id}", ['name' => 'Hack'])
        ->assertForbidden();
});

it('collaborator without UPDATE_ACTIVITY cannot update', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    attachCollaborator($activity, $collaborator);

    $this->withHeaders(asUser($collaborator))
        ->putJson("/api/activities/{$activity->id}", ['name' => 'Hack'])
        ->assertForbidden();
});

// ─── destroy ──────────────────────────────────────────────────────────────────

it('manager can delete their activity', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->deleteJson("/api/activities/{$activity->id}")
        ->assertNoContent();

    expect(Activity::withTrashed()->find($activity->id)->deleted_at)->not->toBeNull();
});

it('admin can delete any activity', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser($admin))
        ->deleteJson("/api/activities/{$activity->id}")
        ->assertNoContent();
});

it('collaborator cannot delete an activity', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    attachCollaborator($activity, $collaborator);

    $this->withHeaders(asUser($collaborator))
        ->deleteJson("/api/activities/{$activity->id}")
        ->assertForbidden();
});

it('random user cannot delete an activity', function () {
    $activity = Activity::factory()->create();
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->deleteJson("/api/activities/{$activity->id}")
        ->assertForbidden();
});

// ─── syncCollaboratorPermissions ──────────────────────────────────────────────

it('manager can sync permissions for a collaborator', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    attachCollaborator($activity, $collaborator);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/collaborators/{$collaborator->id}/permissions", [
            'permissions' => [ActivityPermissionEnum::MANAGE_CYCLES->value],
        ])
        ->assertOk();
});

it('admin can sync permissions for a collaborator', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    attachCollaborator($activity, $collaborator);

    $this->withHeaders(asUser($admin))
        ->putJson("/api/activities/{$activity->id}/collaborators/{$collaborator->id}/permissions", [
            'permissions' => [ActivityPermissionEnum::MANAGE_AVAILABILITIES->value],
        ])
        ->assertOk();
});

it('syncing permissions for a non-collaborator returns 422', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $stranger = User::factory()->create();

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/collaborators/{$stranger->id}/permissions", [
            'permissions' => [ActivityPermissionEnum::MANAGE_CYCLES->value],
        ])
        ->assertUnprocessable();
});

it('non-manager cannot sync collaborator permissions', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    attachCollaborator($activity, $collaborator);

    $randomUser = User::factory()->create();

    $this->withHeaders(asUser($randomUser))
        ->putJson("/api/activities/{$activity->id}/collaborators/{$collaborator->id}/permissions", [
            'permissions' => [ActivityPermissionEnum::MANAGE_CYCLES->value],
        ])
        ->assertForbidden();
});

it('syncing with an invalid permission value returns 422', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    attachCollaborator($activity, $collaborator);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/collaborators/{$collaborator->id}/permissions", [
            'permissions' => ['invalid_permission'],
        ])
        ->assertUnprocessable();
});
