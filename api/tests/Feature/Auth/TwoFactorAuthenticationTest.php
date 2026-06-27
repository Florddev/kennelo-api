<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\TwoFactorStatusNotification;
use App\Services\TwoFactorService;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FAQRCode\Google2FA;

function enableTwoFactor(User $user): string
{
    $secret = app(TwoFactorService::class)->generateSecret();
    $user->two_factor_secret = $secret;
    $user->two_factor_recovery_codes = ['AAAAA-BBBBB', 'CCCCC-DDDDD'];
    $user->two_factor_confirmed_at = now();
    $user->save();

    return $secret;
}

function currentOtp(string $secret): string
{
    return app(Google2FA::class)->getCurrentOtp($secret);
}

test('login without 2FA returns tokens directly', function () {
    $user = User::factory()->create();

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->assertOk()
        ->assertJsonStructure(['access_token', 'refresh_token']);
});

test('login with 2FA returns a challenge instead of tokens', function () {
    $user = User::factory()->create();
    enableTwoFactor($user);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('two_factor', true)
        ->assertJsonStructure(['challenge_token']);
});

test('challenge with a valid TOTP code issues tokens', function () {
    $user = User::factory()->create();
    $secret = enableTwoFactor($user);

    $challenge = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->json('challenge_token');

    $this->postJson('/api/login/two-factor-challenge', [
        'challenge_token' => $challenge,
        'code' => currentOtp($secret),
    ])
        ->assertOk()
        ->assertJsonStructure(['access_token', 'refresh_token', 'user']);
});

test('challenge with an invalid code is rejected', function () {
    $user = User::factory()->create();
    enableTwoFactor($user);

    $challenge = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->json('challenge_token');

    $this->postJson('/api/login/two-factor-challenge', [
        'challenge_token' => $challenge,
        'code' => '000000',
    ])->assertUnprocessable();
});

test('challenge with a recovery code consumes it', function () {
    $user = User::factory()->create();
    enableTwoFactor($user);

    $challenge = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->json('challenge_token');

    $this->postJson('/api/login/two-factor-challenge', [
        'challenge_token' => $challenge,
        'recovery_code' => 'AAAAA-BBBBB',
    ])->assertOk();

    expect($user->fresh()->two_factor_recovery_codes)->not->toContain('AAAAA-BBBBB');
});

test('the challenge token cannot access protected routes', function () {
    $user = User::factory()->create();
    enableTwoFactor($user);

    $challenge = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->json('challenge_token');

    $this->withHeaders(['Authorization' => 'Bearer '.$challenge])
        ->getJson('/api/user')
        ->assertUnauthorized();
});

test('a user can enable two-factor authentication', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->postJson('/api/user/two-factor', ['password' => 'password'])
        ->assertOk()
        ->assertJsonStructure(['qr_svg', 'secret']);

    expect($user->fresh()->two_factor_secret)->not->toBeNull();
    expect($user->fresh()->two_factor_confirmed_at)->toBeNull();
});

test('enabling fails with a wrong password', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->postJson('/api/user/two-factor', ['password' => 'wrong-password'])
        ->assertUnprocessable();

    expect($user->fresh()->two_factor_secret)->toBeNull();
});

test('a user can confirm two-factor authentication', function () {
    $user = User::factory()->create();
    $secret = app(TwoFactorService::class)->generateSecret();
    $user->two_factor_secret = $secret;
    $user->save();

    $this->withHeaders(asUser($user))
        ->postJson('/api/user/two-factor/confirm', ['code' => currentOtp($secret)])
        ->assertOk()
        ->assertJsonStructure(['recovery_codes']);

    expect($user->fresh()->two_factor_confirmed_at)->not->toBeNull();
});

test('confirm fails with an invalid code', function () {
    $user = User::factory()->create();
    $user->two_factor_secret = app(TwoFactorService::class)->generateSecret();
    $user->save();

    $this->withHeaders(asUser($user))
        ->postJson('/api/user/two-factor/confirm', ['code' => '000000'])
        ->assertUnprocessable();
});

test('a user can disable two-factor authentication with their password', function () {
    $user = User::factory()->create();
    enableTwoFactor($user);

    $this->withHeaders(asUser($user))
        ->deleteJson('/api/user/two-factor', ['password' => 'password'])
        ->assertNoContent();

    expect($user->fresh()->two_factor_confirmed_at)->toBeNull();
});

test('disabling fails with a wrong password', function () {
    $user = User::factory()->create();
    enableTwoFactor($user);

    $this->withHeaders(asUser($user))
        ->deleteJson('/api/user/two-factor', ['password' => 'wrong-password'])
        ->assertUnprocessable();
});

test('the user resource exposes two_factor_enabled for self', function () {
    $user = User::factory()->create();
    enableTwoFactor($user);

    $this->withHeaders(asUser($user))
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('data.two_factor_enabled', true);
});

test('the user resource exposes the remaining recovery codes count', function () {
    $user = User::factory()->create();
    enableTwoFactor($user);

    $this->withHeaders(asUser($user))
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('data.two_factor_recovery_codes_count', 2);
});

test('a challenge with remember registers the device and returns a remember token', function () {
    $user = User::factory()->create();
    $secret = enableTwoFactor($user);

    $challenge = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->json('challenge_token');

    $response = $this->postJson('/api/login/two-factor-challenge', [
        'challenge_token' => $challenge,
        'code' => currentOtp($secret),
        'remember' => true,
    ])->assertOk();

    expect($response->json('remember_token'))->not->toBeNull();
    expect($user->rememberedDevices()->count())->toBe(1);
});

test('a remembered device skips the challenge on login', function () {
    $user = User::factory()->create();
    enableTwoFactor($user);

    $rememberToken = app(TwoFactorService::class)->rememberDevice($user);

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
        'remember_token' => $rememberToken,
    ])
        ->assertOk()
        ->assertJsonStructure(['access_token', 'refresh_token']);
});

test('an unknown remember token still triggers the challenge', function () {
    $user = User::factory()->create();
    enableTwoFactor($user);

    $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
        'remember_token' => 'not-a-real-token',
    ])
        ->assertOk()
        ->assertJsonPath('two_factor', true);
});

test('disabling two-factor clears remembered devices', function () {
    $user = User::factory()->create();
    enableTwoFactor($user);
    $user->rememberedDevices()->create([
        'token_hash' => hash('sha256', 'token'),
        'expires_at' => now()->addDays(30),
    ]);

    $this->withHeaders(asUser($user))
        ->deleteJson('/api/user/two-factor', ['password' => 'password'])
        ->assertNoContent();

    expect($user->rememberedDevices()->count())->toBe(0);
});

test('confirming two-factor notifies the user', function () {
    Notification::fake();

    $user = User::factory()->create();
    $secret = app(TwoFactorService::class)->generateSecret();
    $user->two_factor_secret = $secret;
    $user->save();

    $this->withHeaders(asUser($user))
        ->postJson('/api/user/two-factor/confirm', ['code' => currentOtp($secret)])
        ->assertOk();

    Notification::assertSentTo($user, TwoFactorStatusNotification::class);
});

test('disabling two-factor notifies the user', function () {
    Notification::fake();

    $user = User::factory()->create();
    enableTwoFactor($user);

    $this->withHeaders(asUser($user))
        ->deleteJson('/api/user/two-factor', ['password' => 'password'])
        ->assertNoContent();

    Notification::assertSentTo($user, TwoFactorStatusNotification::class);
});
