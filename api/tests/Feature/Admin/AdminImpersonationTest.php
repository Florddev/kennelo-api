<?php

declare(strict_types=1);

use App\Enums\AdminActionTypeEnum;
use App\Models\AdminAction;
use App\Models\User;
use App\Services\JWTService;

it('admin can start impersonating a user', function () {
    $target = User::factory()->create();

    $response = $this->withHeaders(asUser(adminUser()))
        ->postJson("/api/admin/users/{$target->id}/impersonate")
        ->assertOk()
        ->assertJsonPath('data.user.id', $target->id);

    expect($response->json('data.token'))->toBeString()
        ->and($response->json('data.expires_in'))->toBe(900);
});

it('impersonation token authenticates as the target user', function () {
    $target = User::factory()->create();

    $token = $this->withHeaders(asUser(adminUser()))
        ->postJson("/api/admin/users/{$target->id}/impersonate")
        ->json('data.token');

    $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('data.id', $target->id);
});

it('impersonation token carries the impersonator id and a 15 minute ttl', function () {
    $admin = adminUser();
    $target = User::factory()->create();

    $token = $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/users/{$target->id}/impersonate")
        ->json('data.token');

    $payload = app(JWTService::class)->validateToken($token);

    expect($payload->impersonator_id)->toBe($admin->id)
        ->and($payload->exp - $payload->iat)->toBe(900);
});

it('records an audit entry when impersonation starts', function () {
    $admin = adminUser();
    $target = User::factory()->create();

    $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/users/{$target->id}/impersonate")
        ->assertOk();

    expect(AdminAction::where('action', AdminActionTypeEnum::IMPERSONATE_START->value)
        ->where('admin_id', $admin->id)
        ->where('target_user_id', $target->id)
        ->exists())->toBeTrue();
});

it('cannot impersonate another admin', function () {
    $target = adminUser();

    $this->withHeaders(asUser(adminUser()))
        ->postJson("/api/admin/users/{$target->id}/impersonate")
        ->assertForbidden();
});

it('cannot impersonate self', function () {
    $admin = adminUser();

    $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/users/{$admin->id}/impersonate")
        ->assertForbidden();
});

it('forbids non-admin from impersonating', function () {
    $user = User::factory()->create();
    $target = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->postJson("/api/admin/users/{$target->id}/impersonate")
        ->assertForbidden();
});

it('can stop an impersonation session and log it', function () {
    $admin = adminUser();
    $target = User::factory()->create();

    $token = $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/users/{$target->id}/impersonate")
        ->json('data.token');

    $this->withHeaders(['Authorization' => "Bearer {$token}"])
        ->postJson('/api/impersonation/stop')
        ->assertOk();

    expect(AdminAction::where('action', AdminActionTypeEnum::IMPERSONATE_STOP->value)
        ->where('admin_id', $admin->id)
        ->where('target_user_id', $target->id)
        ->exists())->toBeTrue();
});

it('rejects stop when the session is not an impersonation', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->postJson('/api/impersonation/stop')
        ->assertStatus(400);
});
