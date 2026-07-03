<?php

declare(strict_types=1);

use App\Enums\AdminActionTypeEnum;
use App\Models\AdminAction;
use App\Models\User;

it('logs a ban action', function () {
    $admin = adminUser();
    $target = User::factory()->create();

    $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/users/{$target->id}/ban", ['reason' => 'x'])
        ->assertOk();

    expect(AdminAction::where('action', AdminActionTypeEnum::BAN->value)
        ->where('admin_id', $admin->id)
        ->where('target_user_id', $target->id)
        ->exists())->toBeTrue();
});

it('logs a role assignment', function () {
    $admin = adminUser();
    $target = User::factory()->create();

    $this->withHeaders(asUser($admin))
        ->putJson("/api/admin/users/{$target->id}/roles", ['roles' => ['user']])
        ->assertOk();

    expect(AdminAction::where('action', AdminActionTypeEnum::ASSIGN_ROLES->value)->exists())->toBeTrue();
});

it('logs a force password reset', function () {
    $admin = adminUser();
    $target = User::factory()->create();

    $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/users/{$target->id}/force-password-reset")
        ->assertOk();

    expect(AdminAction::where('action', AdminActionTypeEnum::FORCE_PASSWORD_RESET->value)->exists())->toBeTrue();
});

it('admin can list audit actions', function () {
    $admin = adminUser();
    $target = User::factory()->create();

    $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/users/{$target->id}/ban", ['reason' => 'x'])
        ->assertOk();

    $this->withHeaders(asUser($admin))
        ->getJson('/api/admin/audit-actions')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'action', 'admin_id', 'target_user_id', 'created_at']], 'meta']);
});

it('can filter audit actions by target user', function () {
    $admin = adminUser();
    $a = User::factory()->create();
    $b = User::factory()->create();

    $this->withHeaders(asUser($admin))->postJson("/api/admin/users/{$a->id}/ban", ['reason' => 'x'])->assertOk();
    $this->withHeaders(asUser($admin))->postJson("/api/admin/users/{$b->id}/ban", ['reason' => 'y'])->assertOk();

    $this->withHeaders(asUser($admin))
        ->getJson("/api/admin/audit-actions?user_id={$a->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('forbids non-admin from viewing audit actions', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/admin/audit-actions')
        ->assertForbidden();
});
