<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// ─── Profile ─────────────────────────────────────────────────────────────────

it('returns current authenticated user', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

it('updates own profile', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->putJson('/api/user/profile', ['first_name' => 'Alice'])
        ->assertOk()
        ->assertJsonPath('data.first_name', 'Alice');
});

it('user can view own profile via show endpoint', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson("/api/users/{$user->id}")
        ->assertOk();
});

it('user cannot view another user profile', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson("/api/users/{$other->id}")
        ->assertForbidden();
});

it('admin can view any user profile', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $other = User::factory()->create();

    $this->withHeaders(asUser($admin))
        ->getJson("/api/users/{$other->id}")
        ->assertOk();
});

// ─── Password ─────────────────────────────────────────────────────────────────

it('changes password with correct current password', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->putJson('/api/user/password', [
            'current_password' => 'password',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
        ->assertOk()
        ->assertJsonPath('message', 'Password changed successfully');
});

it('rejects password change with wrong current password', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->putJson('/api/user/password', [
            'current_password' => 'wrong-password',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
        ->assertUnprocessable();
});

it('rejects password change when confirmation does not match', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->putJson('/api/user/password', [
            'current_password' => 'password',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'DifferentPassword!',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

// ─── Email ────────────────────────────────────────────────────────────────────

it('changes email with correct password', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->putJson('/api/user/email', [
            'email' => 'newemail@example.com',
            'password' => 'password',
        ])
        ->assertOk()
        ->assertJsonPath('data.email', 'newemail@example.com');
});

it('rejects email change with wrong password', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->putJson('/api/user/email', [
            'email' => 'newemail@example.com',
            'password' => 'wrong',
        ])
        ->assertUnprocessable();
});

it('rejects email change when email already taken', function () {
    $existing = User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->putJson('/api/user/email', [
            'email' => 'taken@example.com',
            'password' => 'password',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

// ─── Address ─────────────────────────────────────────────────────────────────

it('creates address for user', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->putJson('/api/user/address', [
            'line1' => '10 rue de la Paix',
            'postal_code' => '75001',
            'city' => 'Paris',
            'country' => 'FR',
        ])
        ->assertOk()
        ->assertJsonPath('data.city', 'Paris');
});

it('returns address in GET /user when set', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->putJson('/api/user/address', [
            'line1' => '10 rue de la Paix',
            'postal_code' => '75001',
            'city' => 'Paris',
            'country' => 'FR',
        ]);

    $this->withHeaders(asUser($user))
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('data.address.city', 'Paris');
});

it('updates existing address', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->putJson('/api/user/address', [
            'line1' => '10 rue de la Paix',
            'postal_code' => '75001',
            'city' => 'Paris',
            'country' => 'FR',
        ]);

    $this->withHeaders(asUser($user))
        ->putJson('/api/user/address', [
            'line1' => '5 avenue Montaigne',
            'postal_code' => '75008',
            'city' => 'Paris',
            'country' => 'FR',
        ])
        ->assertOk()
        ->assertJsonPath('data.postal_code', '75008');
});

it('deletes user address', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->putJson('/api/user/address', [
            'line1' => '10 rue de la Paix',
            'postal_code' => '75001',
            'city' => 'Paris',
            'country' => 'FR',
        ]);

    $this->withHeaders(asUser($user))
        ->deleteJson('/api/user/address')
        ->assertOk();
});

it('returns 404 when deleting non-existing address', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->deleteJson('/api/user/address')
        ->assertNotFound();
});

// ─── Identity Verification ────────────────────────────────────────────────────

it('returns null when no identity verification submitted', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/user/identity-verification')
        ->assertOk()
        ->assertJsonPath('data', null);
});

it('submits identity verification document', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->withHeaders(asUser($user))
        ->postJson('/api/user/identity-verification', ['document' => $file])
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending');

    expect(Storage::disk('local')->allFiles('identity-verifications'))->toHaveCount(1);
});

it('returns latest verification status', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->withHeaders(asUser($user))
        ->postJson('/api/user/identity-verification', ['document' => $file]);

    $this->withHeaders(asUser($user))
        ->getJson('/api/user/identity-verification')
        ->assertOk()
        ->assertJsonPath('data.status', 'pending');
});

// ─── Account deletion (self) ──────────────────────────────────────────────────

it('user can delete their own account', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->deleteJson('/api/user')
        ->assertOk();

    expect(User::withTrashed()->find($user->id)->deleted_at)->not->toBeNull();
});
