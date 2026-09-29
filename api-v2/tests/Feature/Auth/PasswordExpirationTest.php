<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\PasswordExpirationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

test('login with a recent password opens the session directly', function () {
    $user = User::factory()->create(['password_changed_at' => now()]);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('id', $user->id);

    $this->assertAuthenticatedAs($user, 'web');
});

test('login with an expired password asks for a renewal instead of opening the session', function () {
    $user = User::factory()->create(['password_changed_at' => now()->subDays(61)]);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('password_expired', true)
        ->assertJsonMissingPath('id');

    $this->assertGuest('web');
});

test('password renewal opens the session and updates password_changed_at', function () {
    $user = User::factory()->create(['password_changed_at' => now()->subDays(61)]);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->assertJsonPath('password_expired', true);

    $this->postJson('/api/password/renew', [
        'password' => 'NewStr0ng!Passw0rd',
        'password_confirmation' => 'NewStr0ng!Passw0rd',
    ])
        ->assertOk()
        ->assertJsonPath('id', $user->id);

    $this->assertAuthenticatedAs($user, 'web');

    $user->refresh();
    expect($user->password_changed_at->isToday())->toBeTrue();
    expect(Hash::check('NewStr0ng!Passw0rd', $user->password))->toBeTrue();
});

test('password renewal is refused without a pending renewal', function () {
    $this->postJson('/api/password/renew', [
        'password' => 'NewStr0ng!Passw0rd',
        'password_confirmation' => 'NewStr0ng!Passw0rd',
    ])->assertUnauthorized();
});

test('a pending renewal expires', function () {
    $user = User::factory()->create(['password_changed_at' => now()->subDays(61)]);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->assertJsonPath('password_expired', true);

    $this->travel((int) config('auth.password_renewal_ttl') + 1)->minutes();

    $this->postJson('/api/password/renew', [
        'password' => 'NewStr0ng!Passw0rd',
        'password_confirmation' => 'NewStr0ng!Passw0rd',
    ])->assertUnauthorized();
});

test('an oauth-only user is never asked to renew a password', function () {
    $user = User::factory()->create(['google_id' => 'google-1', 'created_at' => now()->subDays(400)]);
    DB::table('users')->where('id', $user->id)->update(['password' => null, 'password_changed_at' => null]);

    $this->withHeaders(asUser($user))
        ->getJson('/api/user')
        ->assertOk();

    expect(app(PasswordExpirationService::class)->isExpired($user->refresh()))->toBeFalse();
});

test('a user with 2FA and an expired password is challenged for 2FA first, then password renewal', function () {
    $user = User::factory()->create(['password_changed_at' => now()->subDays(61)]);
    $secret = enableTwoFactorFor($user);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
        ->assertJsonPath('two_factor', true)
        ->assertJsonMissingPath('password_expired');

    $this->postJson('/api/login/two-factor-challenge', [
        'code' => currentOtpFor($secret),
    ])
        ->assertOk()
        ->assertJsonPath('password_expired', true);

    $this->assertGuest('web');
});
