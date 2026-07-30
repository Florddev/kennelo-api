<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\JWTService;
use App\Services\User\UserService;
use Illuminate\Support\Facades\Password;

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'access_token',
            'refresh_token',
            'token_type',
            'expires_in',
            'user' => ['id', 'first_name', 'last_name', 'email'],
        ]);
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post('/api/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('users can not authenticate with non-existent email', function () {
    $response = $this->post('/api/login', [
        'email' => 'nobody@example.com',
        'password' => 'password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('users can logout', function () {
    $user = User::factory()->create();

    $jwtService = app(JWTService::class);
    $user->load('roles');
    $accessToken = $jwtService->generateAccessToken($user);
    $refreshToken = $jwtService->generateRefreshToken($user);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer '.$accessToken,
    ])->post('/api/logout', [
        'refresh_token' => $refreshToken,
    ]);

    $response->assertNoContent();
});

test('users can refresh their access token', function () {
    $user = User::factory()->create();

    $jwtService = app(JWTService::class);
    $user->load('roles');
    $refreshToken = $jwtService->generateRefreshToken($user);

    $response = $this->post('/api/refresh', [
        'refresh_token' => $refreshToken,
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'access_token',
            'refresh_token',
            'token_type',
            'expires_in',
        ]);
});

test('refreshing rotates and invalidates the old refresh token', function () {
    config(['jwt.blacklist_enabled' => true]);

    $user = User::factory()->create();
    $user->load('roles');
    $refreshToken = app(JWTService::class)->generateRefreshToken($user);

    $first = $this->post('/api/refresh', ['refresh_token' => $refreshToken]);
    $first->assertOk();

    $newRefreshToken = $first->json('refresh_token');
    expect($newRefreshToken)->not->toBeNull();
    expect($newRefreshToken)->not->toBe($refreshToken);

    $this->post('/api/refresh', ['refresh_token' => $refreshToken])
        ->assertUnauthorized();
});

test('token refresh fails without refresh token', function () {
    $response = $this->post('/api/refresh', []);

    $response->assertStatus(400);
});

test('token refresh fails with invalid token', function () {
    $response = $this->post('/api/refresh', [
        'refresh_token' => 'invalid.token.string',
    ]);

    $response->assertUnauthorized();
});

test('token refresh fails when using an access token', function () {
    $user = User::factory()->create();

    $jwtService = app(JWTService::class);
    $user->load('roles');
    $accessToken = $jwtService->generateAccessToken($user);

    $response = $this->post('/api/refresh', [
        'refresh_token' => $accessToken,
    ]);

    $response->assertUnauthorized();
});

test('logout blacklists the access token', function () {
    config(['jwt.blacklist_enabled' => true]);

    $user = User::factory()->create();
    $user->load('roles');
    $accessToken = app(JWTService::class)->generateAccessToken($user);

    $this->withHeaders(['Authorization' => 'Bearer '.$accessToken])
        ->get('/api/user')
        ->assertOk();

    $this->withHeaders(['Authorization' => 'Bearer '.$accessToken])
        ->post('/api/logout')
        ->assertNoContent();

    $this->withHeaders(['Authorization' => 'Bearer '.$accessToken])
        ->get('/api/user')
        ->assertUnauthorized();
});

test('changing the password revokes existing access tokens', function () {
    $user = User::factory()->create();
    $user->load('roles');
    $accessToken = app(JWTService::class)->generateAccessToken($user);

    $this->withHeaders(['Authorization' => 'Bearer '.$accessToken])
        ->put('/api/user/password', [
            'current_password' => 'password',
            'password' => 'NewPassw0rd!23',
            'password_confirmation' => 'NewPassw0rd!23',
        ])
        ->assertOk();

    $this->withHeaders(['Authorization' => 'Bearer '.$accessToken])
        ->get('/api/user')
        ->assertUnauthorized();
});

test('changing the password revokes existing refresh tokens', function () {
    config(['jwt.blacklist_enabled' => true]);

    $user = User::factory()->create();
    $user->load('roles');
    $refreshToken = app(JWTService::class)->generateRefreshToken($user);

    app(UserService::class)->changePassword($user, [
        'current_password' => 'password',
        'password' => 'NewPassw0rd!23',
    ]);

    $this->post('/api/refresh', ['refresh_token' => $refreshToken])
        ->assertUnauthorized();
});

test('resetting the password revokes existing access tokens', function () {
    $user = User::factory()->create();
    $user->load('roles');
    $accessToken = app(JWTService::class)->generateAccessToken($user);

    $token = Password::createToken($user);

    $this->post('/api/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewPassw0rd!23',
        'password_confirmation' => 'NewPassw0rd!23',
    ])->assertOk();

    $this->withHeaders(['Authorization' => 'Bearer '.$accessToken])
        ->get('/api/user')
        ->assertUnauthorized();
});
