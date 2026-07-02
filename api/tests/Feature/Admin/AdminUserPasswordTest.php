<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

it('admin can trigger a password reset email', function () {
    Notification::fake();
    $target = User::factory()->create();

    $this->withHeaders(asUser(adminUser()))
        ->postJson("/api/admin/users/{$target->id}/force-password-reset")
        ->assertOk()
        ->assertJsonPath('message', 'Password reset link sent successfully');

    Notification::assertSentTo($target, ResetPassword::class);
});

it('forbids non-admin from forcing password reset', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->postJson("/api/admin/users/{$other->id}/force-password-reset")
        ->assertForbidden();
});
