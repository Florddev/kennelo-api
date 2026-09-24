<?php

declare(strict_types=1);

use App\Enums\UserStatusEnum;
use App\Models\User;

it('admin can bulk deactivate users', function () {
    $admin = adminUser();
    $a = User::factory()->create();
    $b = User::factory()->create();

    $this->withHeaders(asUser($admin))
        ->postJson('/api/admin/users/bulk/status', [
            'user_ids' => [$a->id, $b->id],
            'status' => UserStatusEnum::INACTIVE->value,
        ])
        ->assertOk()
        ->assertJsonPath('data.affected', 2);

    expect(User::withInactive()->find($a->id)->status)->toBe(UserStatusEnum::INACTIVE);
});

it('bulk status skips admins and the acting admin', function () {
    $admin = adminUser();
    $otherAdmin = adminUser();
    $regular = User::factory()->create();

    $this->withHeaders(asUser($admin))
        ->postJson('/api/admin/users/bulk/status', [
            'user_ids' => [$admin->id, $otherAdmin->id, $regular->id],
            'status' => UserStatusEnum::INACTIVE->value,
        ])
        ->assertOk()
        ->assertJsonPath('data.affected', 1);

    expect(User::withInactive()->find($admin->id)->status)->toBe(UserStatusEnum::ACTIVE)
        ->and(User::withInactive()->find($otherAdmin->id)->status)->toBe(UserStatusEnum::ACTIVE);
});

it('admin can bulk assign roles', function () {
    $admin = adminUser();
    $a = User::factory()->create();
    $b = User::factory()->create();

    $this->withHeaders(asUser($admin))
        ->postJson('/api/admin/users/bulk/roles', [
            'user_ids' => [$a->id, $b->id],
            'action' => 'assign',
            'roles' => ['admin'],
        ])
        ->assertOk();

    expect($a->fresh()->hasRole('admin'))->toBeTrue()
        ->and($b->fresh()->hasRole('admin'))->toBeTrue();
});

it('admin can bulk remove roles', function () {
    $admin = adminUser();
    $a = User::factory()->create();
    $a->assignRole('admin');

    $this->withHeaders(asUser($admin))
        ->postJson('/api/admin/users/bulk/roles', [
            'user_ids' => [$a->id],
            'action' => 'remove',
            'roles' => ['admin'],
        ])
        ->assertOk();

    expect($a->fresh()->hasRole('admin'))->toBeFalse();
});

it('bulk status rejects the banned status', function () {
    $admin = adminUser();
    $a = User::factory()->create();

    $this->withHeaders(asUser($admin))
        ->postJson('/api/admin/users/bulk/status', [
            'user_ids' => [$a->id],
            'status' => UserStatusEnum::BANNED->value,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});

it('exposes no bulk delete endpoint', function () {
    $admin = adminUser();
    $a = User::factory()->create();

    $this->withHeaders(asUser($admin))
        ->postJson('/api/admin/users/bulk/delete', ['user_ids' => [$a->id]])
        ->assertNotFound();

    expect(User::find($a->id))->not->toBeNull();
});

it('admin can export users as csv', function () {
    $admin = adminUser();
    User::factory()->count(2)->create();

    $response = $this->withHeaders(asUser($admin))->get('/api/admin/users/export');

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('text/csv');
});

it('forbids non-admin from bulk actions', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->postJson('/api/admin/users/bulk/status', [
            'user_ids' => [$user->id],
            'status' => UserStatusEnum::INACTIVE->value,
        ])
        ->assertForbidden();
});
