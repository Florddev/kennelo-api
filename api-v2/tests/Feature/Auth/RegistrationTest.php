<?php

declare(strict_types=1);

use App\Models\User;

test('new users can register', function () {
    $response = $this->post('/api/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'password' => 'Str0ng!Passw0rd',
        'password_confirmation' => 'Str0ng!Passw0rd',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.email', 'test@example.com')
        ->assertJsonMissingPath('access_token');

    $this->assertAuthenticated('web');
});

test('registration fails with duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->post('/api/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'taken@example.com',
        'password' => 'Str0ng!Passw0rd',
        'password_confirmation' => 'Str0ng!Passw0rd',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('registration fails with missing required fields', function () {
    $response = $this->post('/api/register', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'password']);
});

test('registration fails with password confirmation mismatch', function () {
    $response = $this->post('/api/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'password' => 'Str0ng!Passw0rd',
        'password_confirmation' => 'Different!Passw0rd',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});
