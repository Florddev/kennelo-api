<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * @return array{email: string, password: string, device_name: string}
 */
function tokenCredentials(User $user): array
{
    return ['email' => $user->email, 'password' => 'password', 'device_name' => 'iPhone de Léa'];
}

it('issues a token that authenticates the following requests without a session', function () {
    $user = User::factory()->create(['password_changed_at' => now()]);

    $token = $this->postJson('/api/auth/token', tokenCredentials($user))
        ->assertCreated()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', $user->email)
        ->json('token');

    expect($user->tokens()->sole()->name)->toBe('iPhone de Léa');

    app('auth')->forgetGuards();

    $this->withToken($token)->getJson('/api/user')->assertOk()->assertJsonPath('id', $user->id);
});

it('refuses wrong credentials and a missing device name', function () {
    $user = User::factory()->create();

    $this->postJson('/api/auth/token', [...tokenCredentials($user), 'password' => 'wrong'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    $this->postJson('/api/auth/token', ['email' => $user->email, 'password' => 'password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('device_name');

    expect($user->tokens()->count())->toBe(0);
});

it('asks for the two-factor code, then issues the token with it', function () {
    $user = User::factory()->create(['password_changed_at' => now()]);
    $secret = enableTwoFactorFor($user);

    $this->postJson('/api/auth/token', tokenCredentials($user))
        ->assertOk()
        ->assertExactJson(['two_factor' => true]);

    $this->postJson('/api/auth/token', [...tokenCredentials($user), 'code' => 'not-a-code'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');

    expect($user->tokens()->count())->toBe(0);

    $this->postJson('/api/auth/token', [...tokenCredentials($user), 'code' => currentOtpFor($secret)])
        ->assertCreated()
        ->assertJsonStructure(['token', 'user' => ['id', 'roles']]);

    expect($user->tokens()->count())->toBe(1);
});

it('asks for a new password when it has expired, and checks the recovery code only once', function () {
    $user = User::factory()->create(['password_changed_at' => now()->subDays(61)]);
    enableTwoFactorFor($user);
    $credentials = [...tokenCredentials($user), 'recovery_code' => 'aaaaa-bbbbb'];

    $this->postJson('/api/auth/token', $credentials)
        ->assertOk()
        ->assertExactJson(['password_expired' => true]);

    $this->postJson('/api/auth/token', [...$credentials, 'new_password' => 'NewStr0ng!Passw0rd', 'new_password_confirmation' => 'NewStr0ng!Passw0rd'])
        ->assertCreated();

    $user->refresh();

    expect(Hash::check('NewStr0ng!Passw0rd', (string) $user->password))->toBeTrue()
        ->and($user->two_factor_recovery_codes)->toBe([])
        ->and($user->tokens()->count())->toBe(1);
});

it('revokes the current token only', function () {
    $user = User::factory()->create();
    $token = $user->createToken('iPhone')->plainTextToken;
    $user->createToken('iPad');

    $this->withToken($token)->deleteJson('/api/auth/token')->assertNoContent();

    expect($user->tokens()->pluck('name')->all())->toBe(['iPad']);

    app('auth')->forgetGuards();

    $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
});

it('revokes the token of a mobile client that logs out', function () {
    $user = User::factory()->create();
    $token = $user->createToken('iPhone')->plainTextToken;

    $this->withToken($token)->postJson('/api/logout')->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});
