<?php

declare(strict_types=1);

use App\Enums\UserStatusEnum;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;

it('admin can ban a user permanently', function () {
    Notification::fake();
    $target = User::factory()->create();

    $this->withHeaders(asUser(adminUser()))
        ->postJson("/api/admin/users/{$target->id}/ban", ['reason' => 'Spam'])
        ->assertOk();

    $banned = User::withInactive()->find($target->id);

    expect($banned->status)->toBe(UserStatusEnum::BANNED)
        ->and($banned->ban_reason)->toBe('Spam')
        ->and($banned->banned_until)->toBeNull()
        ->and($banned->banned_at)->not->toBeNull();

    Notification::assertSentTo($target, AppNotification::class);
});

it('admin can ban a user temporarily', function () {
    $target = User::factory()->create();
    $until = now()->addDays(7)->toISOString();

    $this->withHeaders(asUser(adminUser()))
        ->postJson("/api/admin/users/{$target->id}/ban", ['reason' => 'Timeout', 'banned_until' => $until])
        ->assertOk();

    expect(User::withInactive()->find($target->id)->banned_until)->not->toBeNull();
});

it('banned user is excluded from default queries', function () {
    $target = User::factory()->create();

    $this->withHeaders(asUser(adminUser()))
        ->postJson("/api/admin/users/{$target->id}/ban", ['reason' => 'x'])
        ->assertOk();

    expect(User::find($target->id))->toBeNull()
        ->and(User::withInactive()->find($target->id))->not->toBeNull();
});

it('admin cannot ban another admin', function () {
    $target = adminUser();

    $this->withHeaders(asUser(adminUser()))
        ->postJson("/api/admin/users/{$target->id}/ban", ['reason' => 'x'])
        ->assertForbidden();
});

it('admin cannot ban themselves', function () {
    $admin = adminUser();

    $this->withHeaders(asUser($admin))
        ->postJson("/api/admin/users/{$admin->id}/ban", ['reason' => 'x'])
        ->assertForbidden();
});

it('ban requires a reason', function () {
    $target = User::factory()->create();

    $this->withHeaders(asUser(adminUser()))
        ->postJson("/api/admin/users/{$target->id}/ban", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['reason']);
});

it('admin can unban a user', function () {
    Notification::fake();
    $target = User::factory()->create();

    $this->withHeaders(asUser(adminUser()))
        ->postJson("/api/admin/users/{$target->id}/ban", ['reason' => 'x'])
        ->assertOk();

    $this->withHeaders(asUser(adminUser()))
        ->deleteJson("/api/admin/users/{$target->id}/ban")
        ->assertOk();

    $unbanned = User::withInactive()->find($target->id);

    expect($unbanned->status)->toBe(UserStatusEnum::ACTIVE)
        ->and($unbanned->ban_reason)->toBeNull()
        ->and($unbanned->banned_by)->toBeNull();
});

it('forbids non-admin from banning', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->postJson("/api/admin/users/{$other->id}/ban", ['reason' => 'x'])
        ->assertForbidden();
});
