<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

/**
 * Empreinte que Sanctum enregistre dans une session ouverte avec le mot de passe actuel de $user.
 */
function sessionPasswordHash(User $user): string
{
    $guard = Auth::guard('web');

    if (! $guard instanceof SessionGuard) {
        throw new LogicException('The web guard must be a session guard.');
    }

    return $guard->hashPasswordForCookie($user->password);
}

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('email', $user->email)
        ->assertJsonMissingPath('access_token');

    $this->assertAuthenticatedAs($user, 'web');
});

test('a signed-in user can sign in again, as another account, without being redirected', function () {
    $current = User::factory()->create();
    $other = User::factory()->create();

    $this->withHeaders(asUser($current))
        ->postJson('/api/login', ['email' => $other->email, 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('id', $other->id);

    $this->assertAuthenticatedAs($other, 'web');
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    $this->assertGuest('web');
});

test('users can not authenticate with non-existent email', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'nobody@example.com',
        'password' => 'password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    $this->assertGuest('web');
});

test('users can logout', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->postJson('/api/logout')
        ->assertNoContent();

    $this->assertGuest('web');
});

test('changing the password signs out the other sessions', function () {
    $user = User::factory()->create();
    $otherSession = sessionPasswordHash($user);

    app(UserService::class)->changePassword($user, [
        'current_password' => 'password',
        'password' => 'NewPassw0rd!23',
    ]);

    $this->withSession(['password_hash_web' => $otherSession])
        ->withHeaders(asUser($user))
        ->getJson('/api/user')
        ->assertUnauthorized();
});

test('resetting the password signs out the other sessions', function () {
    $user = User::factory()->create();
    $otherSession = sessionPasswordHash($user);

    $this->postJson('/api/reset-password', [
        'token' => Password::createToken($user),
        'email' => $user->email,
        'password' => 'NewPassw0rd!23',
        'password_confirmation' => 'NewPassw0rd!23',
    ])->assertOk();

    $this->withSession(['password_hash_web' => $otherSession])
        ->withHeaders(asUser($user))
        ->getJson('/api/user')
        ->assertUnauthorized();
});

test('a session opened with the current password stays valid', function () {
    $user = User::factory()->create();

    $this->withSession(['password_hash_web' => sessionPasswordHash($user)])
        ->withHeaders(asUser($user))
        ->getJson('/api/user')
        ->assertOk();
});
