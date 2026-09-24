<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/api/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('requesting a reset link does not reveal whether the account exists', function () {
    Notification::fake();

    $user = User::factory()->create();

    $known = $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();
    $unknown = $this->postJson('/api/forgot-password', ['email' => 'nobody@example.com'])->assertOk();

    expect($unknown->json())->toBe($known->json());
    Notification::assertSentTimes(ResetPassword::class, 1);
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/api/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (object $notification) use ($user) {
        $response = $this->post('/api/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'Str0ng!Passw0rd',
            'password_confirmation' => 'Str0ng!Passw0rd',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['status']);

        return true;
    });
});
