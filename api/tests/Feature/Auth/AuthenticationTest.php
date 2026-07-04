<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\JWTService;

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
