<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

beforeEach(function () {
    $this->withoutHeader('Referer');
});

/**
 * @return array{email: string, password: string, device_name: string}
 */
function mobileCredentials(User $user): array
{
    return ['email' => $user->email, 'password' => 'password', 'device_name' => 'iPhone de Léa'];
}

it('issues a token instead of a session when the login carries a device name', function () {
    $user = User::factory()->create(['password_changed_at' => now()]);
    $user->assignRole('user');

    $token = $this->postJson('/api/login', mobileCredentials($user))
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', $user->email)
        ->assertJsonPath('user.roles', ['user'])
        ->json('token');

    expect($user->tokens()->sole()->name)->toBe('iPhone de Léa');

    app('auth')->forgetGuards();

    $this->withToken($token)->getJson('/api/user')->assertOk()->assertJsonPath('id', $user->id);
});

it('asks a client without session for a device name', function () {
    $user = User::factory()->create();

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('device_name');

    $this->postJson('/api/login', [...mobileCredentials($user), 'password' => 'wrong'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');

    expect($user->tokens()->count())->toBe(0);
});

it('carries the two-factor step in a pending token, used once', function () {
    $user = User::factory()->create(['password_changed_at' => now()]);
    $secret = enableTwoFactorFor($user);

    $pendingToken = $this->postJson('/api/login', mobileCredentials($user))
        ->assertOk()
        ->assertJsonPath('two_factor', true)
        ->json('pending_token');

    expect($pendingToken)->toBeString()->and($user->tokens()->count())->toBe(0);

    $this->postJson('/api/login/two-factor-challenge', ['device_name' => 'iPhone de Léa', 'code' => currentOtpFor($secret)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('pending_token');

    $this->postJson('/api/login/two-factor-challenge', ['device_name' => 'iPhone de Léa', 'pending_token' => $pendingToken, 'code' => '000000'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');

    $this->postJson('/api/login/two-factor-challenge', ['device_name' => 'iPhone de Léa', 'pending_token' => $pendingToken, 'code' => currentOtpFor($secret)])
        ->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id', 'roles']]);

    $this->postJson('/api/login/two-factor-challenge', ['device_name' => 'iPhone de Léa', 'pending_token' => $pendingToken, 'code' => currentOtpFor($secret)])
        ->assertUnauthorized();

    expect($user->tokens()->count())->toBe(1);
});

it('renews an expired password after the two-factor step with a new pending token', function () {
    $user = User::factory()->create(['password_changed_at' => now()->subDays(61)]);
    enableTwoFactorFor($user);
    $user->createToken('Ancien iPad');

    $twoFactorToken = $this->postJson('/api/login', mobileCredentials($user))->json('pending_token');

    $renewalToken = $this->postJson('/api/login/two-factor-challenge', ['device_name' => 'iPhone de Léa', 'pending_token' => $twoFactorToken, 'recovery_code' => 'aaaaa-bbbbb'])
        ->assertOk()
        ->assertJsonPath('password_expired', true)
        ->json('pending_token');

    expect($renewalToken)->toBeString()->and($renewalToken)->not->toBe($twoFactorToken);

    $renewal = ['device_name' => 'iPhone de Léa', 'password' => 'NewStr0ng!Passw0rd', 'password_confirmation' => 'NewStr0ng!Passw0rd'];

    $this->postJson('/api/password/renew', [...$renewal, 'pending_token' => $twoFactorToken])->assertUnauthorized();

    $this->postJson('/api/password/renew', [...$renewal, 'pending_token' => $renewalToken])
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonStructure(['token']);

    $user->refresh();

    expect(Hash::check('NewStr0ng!Passw0rd', (string) $user->password))->toBeTrue()
        ->and($user->two_factor_recovery_codes)->toBe([])
        ->and($user->tokens()->pluck('name')->all())->toBe(['iPhone de Léa']);
});

it('lets a pending token expire', function () {
    $user = User::factory()->create(['password_changed_at' => now()]);
    $secret = enableTwoFactorFor($user);

    $pendingToken = $this->postJson('/api/login', mobileCredentials($user))->json('pending_token');

    $this->travel((int) config('auth.two_factor_challenge_ttl') + 1)->minutes();

    $this->postJson('/api/login/two-factor-challenge', ['device_name' => 'iPhone de Léa', 'pending_token' => $pendingToken, 'code' => currentOtpFor($secret)])
        ->assertUnauthorized();
});

it('returns the token to a mobile registration', function () {
    $this->postJson('/api/register', [
        'first_name' => 'Léa',
        'last_name' => 'Martin',
        'email' => 'lea@example.com',
        'password' => 'Str0ng!Passw0rd',
        'password_confirmation' => 'Str0ng!Passw0rd',
        'device_name' => 'iPhone de Léa',
    ])
        ->assertCreated()
        ->assertJsonPath('user.email', 'lea@example.com')
        ->assertJsonStructure(['token']);

    expect(User::query()->where('email', 'lea@example.com')->sole()->tokens()->count())->toBe(1);
});

it('revokes only the current token on logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('iPhone')->plainTextToken;
    $user->createToken('iPad');

    $this->withToken($token)->postJson('/api/logout')->assertNoContent();

    expect($user->tokens()->pluck('name')->all())->toBe(['iPad']);

    app('auth')->forgetGuards();

    $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
});

it('revokes the tokens of the other devices when the password changes', function () {
    $user = User::factory()->create();
    $token = $user->createToken('iPhone')->plainTextToken;
    $user->createToken('iPad');

    $this->withToken($token)
        ->putJson('/api/user/password', [
            'current_password' => 'password',
            'password' => 'NewStr0ng!Passw0rd',
            'password_confirmation' => 'NewStr0ng!Passw0rd',
        ])
        ->assertSuccessful();

    expect($user->tokens()->pluck('name')->all())->toBe(['iPhone']);

    app(UserService::class)->changePassword($user->refresh(), ['current_password' => 'NewStr0ng!Passw0rd', 'password' => 'Other5tr0ng!Passw0rd']);

    expect($user->tokens()->count())->toBe(0);
});

it('revokes every token when the password is reset', function () {
    $user = User::factory()->create();
    $user->createToken('iPhone');

    $this->postJson('/api/reset-password', [
        'token' => Password::createToken($user),
        'email' => $user->email,
        'password' => 'NewStr0ng!Passw0rd',
        'password_confirmation' => 'NewStr0ng!Passw0rd',
    ])->assertOk();

    expect($user->tokens()->count())->toBe(0);
});
