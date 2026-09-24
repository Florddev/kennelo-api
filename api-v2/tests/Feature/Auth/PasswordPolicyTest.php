<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

dataset('weak_passwords', [
    'too short' => 'Sh0rt!Aa',
    'no symbol' => 'NoSymbol1234',
    'no uppercase' => 'no-uppercase-123',
    'no lowercase' => 'NO-LOWERCASE-123',
    'no digit' => 'NoDigit!Password',
]);

test('registration rejects weak passwords', function (string $password) {
    $response = $this->post('/api/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
})->with('weak_passwords');

test('registration accepts a strong password', function () {
    $response = $this->post('/api/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'password' => 'Str0ng!Passw0rd',
        'password_confirmation' => 'Str0ng!Passw0rd',
    ]);

    $response->assertCreated();
});

test('reset password rejects weak passwords', function (string $password) {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/api/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (object $notification) use ($user, $password) {
        $response = $this->post('/api/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        return true;
    });
})->with('weak_passwords');

test('change password rejects weak passwords', function (string $password) {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->putJson('/api/user/password', [
            'current_password' => 'password',
            'password' => $password,
            'password_confirmation' => $password,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
})->with('weak_passwords');

test('change password accepts a strong password', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->putJson('/api/user/password', [
            'current_password' => 'password',
            'password' => 'Str0ng!Passw0rd',
            'password_confirmation' => 'Str0ng!Passw0rd',
        ])
        ->assertNoContent();
});
