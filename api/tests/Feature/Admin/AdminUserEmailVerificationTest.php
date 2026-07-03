<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;

it('admin can mark a user email as verified', function () {
    $target = User::factory()->unverified()->create();

    $this->withHeaders(asUser(adminUser()))
        ->postJson("/api/admin/users/{$target->id}/verify-email")
        ->assertOk();

    expect($target->fresh()->email_verified_at)->not->toBeNull();
});

it('admin can resend a verification email', function () {
    Notification::fake();
    $target = User::factory()->unverified()->create();

    $this->withHeaders(asUser(adminUser()))
        ->postJson("/api/admin/users/{$target->id}/resend-verification")
        ->assertOk();

    Notification::assertSentTo($target, VerifyEmail::class);
});

it('forbids non-admin from verifying email', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->postJson("/api/admin/users/{$other->id}/verify-email")
        ->assertForbidden();
});
