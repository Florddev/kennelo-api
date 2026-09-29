<?php

declare(strict_types=1);

use App\Enums\UserStatusEnum;
use App\Models\User;

// ─── Access control ────────────────────────────────────────────────────────────

it('forbids non-admin from listing users', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/admin/users')
        ->assertForbidden();
});

it('requires authentication on admin routes', function () {
    $this->getJson('/api/admin/users')
        ->assertUnauthorized();
});

// ─── Index ─────────────────────────────────────────────────────────────────────

it('admin can list users', function () {
    User::factory()->count(3)->create();

    $this->withHeaders(asUser(adminUser()))
        ->getJson('/api/admin/users')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta']);
});

it('admin finds users by name or email whatever the case', function () {
    $lea = User::factory()->create(['first_name' => 'Léa', 'last_name' => 'Dupont', 'email' => 'lea.dupont@example.com']);
    User::factory()->create(['last_name' => 'Martin', 'email' => 'martin@example.com']);

    $this->withHeaders(asUser(adminUser()))
        ->getJson('/api/admin/users?search=DUPONT')
        ->assertOk()
        ->assertJsonPath('data.*.id', [$lea->id]);
    $this->getJson('/api/admin/users?search=Lea.Dupont@')->assertJsonPath('data.*.id', [$lea->id]);
});

// ─── Show ──────────────────────────────────────────────────────────────────────

it('admin can view any user detail', function () {
    $target = User::factory()->create();

    $this->withHeaders(asUser(adminUser()))
        ->getJson("/api/admin/users/{$target->id}")
        ->assertOk()
        ->assertJsonPath('id', $target->id);
});

// ─── Update ──────────────────────────────────────────────────────────────────────

it('admin can update any user profile', function () {
    $target = User::factory()->create();

    $this->withHeaders(asUser(adminUser()))
        ->patchJson("/api/admin/users/{$target->id}", ['first_name' => 'Updated'])
        ->assertOk()
        ->assertJsonPath('first_name', 'Updated');
});

it('forbids non-admin from updating another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->patchJson("/api/admin/users/{$other->id}", ['first_name' => 'Hacked'])
        ->assertForbidden();
});

// ─── Destroy ──────────────────────────────────────────────────────────────────

it('admin can delete a user', function () {
    $target = User::factory()->create();

    $this->withHeaders(asUser(adminUser()))
        ->deleteJson("/api/admin/users/{$target->id}")
        ->assertNoContent();

    expect(User::withTrashed()->find($target->id)->deleted_at)->not->toBeNull();
});

it('forbids non-admin from deleting another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->deleteJson("/api/admin/users/{$other->id}")
        ->assertForbidden();
});

// ─── Status ────────────────────────────────────────────────────────────────────

it('admin can deactivate a user', function () {
    $target = User::factory()->create();

    $this->withHeaders(asUser(adminUser()))
        ->putJson("/api/admin/users/{$target->id}/status", ['status' => UserStatusEnum::INACTIVE->value])
        ->assertOk();

    expect(User::withInactive()->find($target->id)->status)->toBe(UserStatusEnum::INACTIVE);
});

it('admin cannot deactivate themselves', function () {
    $admin = adminUser();

    $this->withHeaders(asUser($admin))
        ->putJson("/api/admin/users/{$admin->id}/status", ['status' => UserStatusEnum::INACTIVE->value])
        ->assertForbidden();
});

// ─── Roles ─────────────────────────────────────────────────────────────────────

it('admin can assign roles to a user', function () {
    $target = User::factory()->create();

    $this->withHeaders(asUser(adminUser()))
        ->putJson("/api/admin/users/{$target->id}/roles", ['roles' => ['user']])
        ->assertOk()
        ->assertJsonPath('roles.0', 'user');
});

it('admin can remove a role from a user', function () {
    $target = User::factory()->create();
    $target->assignRole('user');

    $this->withHeaders(asUser(adminUser()))
        ->deleteJson("/api/admin/users/{$target->id}/roles/user")
        ->assertOk();
});

it('forbids non-admin from managing roles', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->putJson("/api/admin/users/{$other->id}/roles", ['roles' => ['admin']])
        ->assertForbidden();
});
