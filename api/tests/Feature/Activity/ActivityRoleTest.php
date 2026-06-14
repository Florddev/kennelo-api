<?php

declare(strict_types=1);

use App\Enums\ActivityPermissionEnum;
use App\Enums\CollaboratorStatusEnum;
use App\Models\Activity;
use App\Models\ActivityRole;
use App\Models\User;

// ─── index ──────────────────────────────────────────────────────────────────────

it('manager can list activity roles', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    ActivityRole::factory()->count(2)->create(['activity_id' => $activity->id]);

    $this->withHeaders(asUser($manager))
        ->getJson("/api/activities/{$activity->id}/roles")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('non-manager cannot list activity roles', function () {
    $activity = Activity::factory()->create();
    $stranger = User::factory()->create();

    $this->withHeaders(asUser($stranger))
        ->getJson("/api/activities/{$activity->id}/roles")
        ->assertForbidden();
});

// ─── store ──────────────────────────────────────────────────────────────────────

it('manager can create a role with permissions', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/activities/{$activity->id}/roles", [
            'name' => 'Secrétaire',
            'permissions' => [
                ActivityPermissionEnum::MANAGE_MESSAGES->value,
                ActivityPermissionEnum::MANAGE_BOOKINGS->value,
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Secrétaire')
        ->assertJsonCount(2, 'data.permissions');

    expect($activity->roles()->count())->toBe(1);
});

it('admin can create a role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser($admin))
        ->postJson("/api/activities/{$activity->id}/roles", [
            'name' => 'Comptable',
            'permissions' => [],
        ])
        ->assertCreated();
});

it('non-manager cannot create a role', function () {
    $activity = Activity::factory()->create();
    $stranger = User::factory()->create();

    $this->withHeaders(asUser($stranger))
        ->postJson("/api/activities/{$activity->id}/roles", [
            'name' => 'Hack',
            'permissions' => [],
        ])
        ->assertForbidden();
});

it('creating a role with an invalid permission returns 422', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/activities/{$activity->id}/roles", [
            'name' => 'Invalid',
            'permissions' => ['not_a_real_permission'],
        ])
        ->assertUnprocessable();
});

// ─── update ─────────────────────────────────────────────────────────────────────

it('manager can update a role name and permissions', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $role = ActivityRole::factory()->create(['activity_id' => $activity->id]);
    $role->permissions()->create(['permission' => ActivityPermissionEnum::MANAGE_BOOKINGS->value]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/roles/{$role->id}", [
            'name' => 'Gérant',
            'permissions' => [ActivityPermissionEnum::MANAGE_CYCLES->value],
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Gérant')
        ->assertJsonPath('data.permissions', [ActivityPermissionEnum::MANAGE_CYCLES->value]);

    expect($role->permissions()->count())->toBe(1);
});

it('updating a role of another activity returns 404', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $otherActivity = Activity::factory()->create();
    $role = ActivityRole::factory()->create(['activity_id' => $otherActivity->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/roles/{$role->id}", [
            'name' => 'X',
            'permissions' => [],
        ])
        ->assertNotFound();
});

// ─── destroy ────────────────────────────────────────────────────────────────────

it('manager can delete an unassigned role', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $role = ActivityRole::factory()->create(['activity_id' => $activity->id]);

    $this->withHeaders(asUser($manager))
        ->deleteJson("/api/activities/{$activity->id}/roles/{$role->id}")
        ->assertNoContent();

    expect(ActivityRole::find($role->id))->toBeNull();
});

it('deleting a role still assigned to a collaborator returns 422', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $role = ActivityRole::factory()->create(['activity_id' => $activity->id]);

    $collaborator = User::factory()->create();
    $activity->collaboratorLinks()->create([
        'user_id' => $collaborator->id,
        'status' => CollaboratorStatusEnum::ACCEPTED->value,
        'role_id' => $role->id,
    ]);

    $this->withHeaders(asUser($manager))
        ->deleteJson("/api/activities/{$activity->id}/roles/{$role->id}")
        ->assertUnprocessable();

    expect(ActivityRole::find($role->id))->not->toBeNull();
});
