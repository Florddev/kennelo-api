<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\JWTService;
use App\Services\PasswordExpirationService;
use App\Services\TwoFactorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FAQRCode\Google2FA;

function enableTwoFactorFor(User $user): string
{
    $secret = app(TwoFactorService::class)->generateSecret();
    $user->two_factor_secret = $secret;
    $user->two_factor_recovery_codes = ['AAAAA-BBBBB'];
    $user->two_factor_confirmed_at = now();
    $user->save();

    return $secret;
}

function currentOtpFor(string $secret): string
{
    return app(Google2FA::class)->getCurrentOtp($secret);
}

test('login with a recent password returns tokens directly', function () {
    $user = User::factory()->create(['password_changed_at' => now()]);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->assertOk()
        ->assertJsonStructure(['access_token', 'refresh_token']);
});

test('login with an expired password returns a challenge instead of tokens', function () {
    $user = User::factory()->create(['password_changed_at' => now()->subDays(61)]);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('password_expired', true)
        ->assertJsonStructure(['challenge_token']);
});

test('password renewal issues tokens and updates password_changed_at', function () {
    $user = User::factory()->create(['password_changed_at' => now()->subDays(61)]);

    $challenge = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->json('challenge_token');

    $response = $this->postJson('/api/password/renew', [
        'challenge_token' => $challenge,
        'password' => 'NewStr0ng!Passw0rd',
        'password_confirmation' => 'NewStr0ng!Passw0rd',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['access_token', 'refresh_token', 'user']);

    $user->refresh();
    expect($user->password_changed_at->isToday())->toBeTrue();
    expect(Hash::check('NewStr0ng!Passw0rd', $user->password))->toBeTrue();
});

test('password renewal fails with an invalid challenge token', function () {
    $this->postJson('/api/password/renew', [
        'challenge_token' => 'not-a-real-token',
        'password' => 'NewStr0ng!Passw0rd',
        'password_confirmation' => 'NewStr0ng!Passw0rd',
    ])->assertUnauthorized();
});

test('an oauth-only user is never asked to renew a password', function () {
    $user = User::factory()->create(['google_id' => 'google-1', 'created_at' => now()->subDays(400)]);
    DB::table('users')->where('id', $user->id)->update(['password' => null, 'password_changed_at' => null]);

    $jwtService = app(JWTService::class);
    $user->refresh()->load('roles');

    $accessToken = $jwtService->generateAccessToken($user);

    $this->withHeaders(['Authorization' => 'Bearer '.$accessToken])
        ->getJson('/api/user')
        ->assertOk();

    expect(app(PasswordExpirationService::class)->isExpired($user))->toBeFalse();
});

test('a user with 2FA and an expired password is challenged for 2FA first, then password renewal', function () {
    $user = User::factory()->create(['password_changed_at' => now()->subDays(61)]);
    $secret = enableTwoFactorFor($user);

    $login = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->assertJsonPath('two_factor', true)
        ->assertJsonMissingPath('password_expired');

    $challenge = $login->json('challenge_token');

    $this->postJson('/api/login/two-factor-challenge', [
        'challenge_token' => $challenge,
        'code' => currentOtpFor($secret),
    ])
        ->assertOk()
        ->assertJsonPath('password_expired', true)
        ->assertJsonStructure(['challenge_token']);
});
