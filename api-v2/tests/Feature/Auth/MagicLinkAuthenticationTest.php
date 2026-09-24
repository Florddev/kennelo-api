<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\MagicLinkNotification;
use App\Services\TwoFactorService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PragmaRX\Google2FAQRCode\Google2FA;

function magicLinkUrlFor(User $user, int $minutes = 15): string
{
    return URL::temporarySignedRoute(
        'magic-link.verify',
        now()->addMinutes($minutes),
        ['id' => $user->id],
    );
}

test('a magic link is sent for an existing email', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->postJson('/api/magic-link', ['email' => $user->email])
        ->assertOk();

    Notification::assertSentTo($user, MagicLinkNotification::class);
});

test('no magic link is sent for an unknown email and the response stays generic', function () {
    Notification::fake();

    $this->postJson('/api/magic-link', ['email' => 'nobody@example.com'])
        ->assertOk();

    Notification::assertNothingSent();
});

test('a valid magic link authenticates the user', function () {
    $user = User::factory()->create();

    $this->getJson(magicLinkUrlFor($user))
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);

    $this->assertAuthenticatedAs($user, 'web');
});

test('a magic link cannot be used twice', function () {
    $user = User::factory()->create();
    $url = magicLinkUrlFor($user);

    $this->get($url)->assertOk();

    $this->get($url)->assertStatus(410);
});

test('an expired magic link is rejected', function () {
    $user = User::factory()->create();

    $this->get(magicLinkUrlFor($user, -1))
        ->assertForbidden();
});

test('a magic link for a 2FA-enabled user returns a challenge', function () {
    $user = User::factory()->create();
    app(TwoFactorService::class)->generateSecret();
    $secret = app(Google2FA::class)->generateSecretKey();
    $user->two_factor_secret = $secret;
    $user->two_factor_confirmed_at = now();
    $user->save();

    $this->get(magicLinkUrlFor($user))
        ->assertOk()
        ->assertJsonPath('two_factor', true);

    $this->assertGuest('web');
});

test('a magic link for a user with an expired password returns a password renewal challenge', function () {
    $user = User::factory()->create(['password_changed_at' => now()->subDays(61)]);

    $this->get(magicLinkUrlFor($user))
        ->assertOk()
        ->assertJsonPath('password_expired', true);

    $this->assertGuest('web');
});
