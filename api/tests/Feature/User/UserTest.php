<?php

declare(strict_types=1);

use App\Enums\IdentityVerificationStatusEnum;
use App\Enums\UserStatusEnum;
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
    Storage::fake('private');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->withHeaders(asUser($user))
        ->postJson('/api/user/identity-verification', ['document' => $file])
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending');
});

it('returns latest verification status', function () {
    Storage::fake('private');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->withHeaders(asUser($user))
        ->postJson('/api/user/identity-verification', ['document' => $file]);

    $this->withHeaders(asUser($user))
        ->getJson('/api/user/identity-verification')
        ->assertOk()
        ->assertJsonPath('data.status', 'pending');
});

// ─── Admin: update user ────────────────────────────────────────────────────────

it('admin can update any user profile', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $target = User::factory()->create();

    $this->withHeaders(asUser($admin))
        ->patchJson("/api/users/{$target->id}", ['first_name' => 'Updated'])
        ->assertOk()
        ->assertJsonPath('data.first_name', 'Updated');
});

it('non-admin cannot update another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->patchJson("/api/users/{$other->id}", ['first_name' => 'Hacked'])
        ->assertForbidden();
});

// ─── Admin: destroy ──────────────────────────────────────────────────────────

it('admin can delete a user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $target = User::factory()->create();

    $this->withHeaders(asUser($admin))
        ->deleteJson("/api/users/{$target->id}")
        ->assertOk();

    expect(User::withTrashed()->find($target->id)->deleted_at)->not->toBeNull();
});

it('non-admin cannot delete another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->deleteJson("/api/users/{$other->id}")
        ->assertForbidden();
});

it('user can delete their own account', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->deleteJson('/api/user')
        ->assertOk();

    expect(User::withTrashed()->find($user->id)->deleted_at)->not->toBeNull();
});

// ─── Admin: status ────────────────────────────────────────────────────────────

it('admin can deactivate a user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $target = User::factory()->create();

    $this->withHeaders(asUser($admin))
        ->putJson("/api/users/{$target->id}/status", ['status' => UserStatusEnum::INACTIVE->value])
        ->assertOk();

    expect(User::withInactive()->find($target->id)->status)->toBe(UserStatusEnum::INACTIVE);
});

it('admin cannot deactivate themselves', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->withHeaders(asUser($admin))
        ->putJson("/api/users/{$admin->id}/status", ['status' => UserStatusEnum::INACTIVE->value])
        ->assertForbidden();
});

// ─── Admin: roles ─────────────────────────────────────────────────────────────

it('admin can assign roles to a user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $target = User::factory()->create();

    $this->withHeaders(asUser($admin))
        ->putJson("/api/users/{$target->id}/roles", ['roles' => ['user']])
        ->assertOk()
        ->assertJsonPath('data.roles.0', 'user');
});

it('admin can remove a role from a user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $target = User::factory()->create();
    $target->assignRole('user');

    $this->withHeaders(asUser($admin))
        ->deleteJson("/api/users/{$target->id}/roles/user")
        ->assertOk();
});

it('non-admin cannot manage roles', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->putJson("/api/users/{$other->id}/roles", ['roles' => ['admin']])
        ->assertForbidden();
});

// ─── Admin: review identity verification ─────────────────────────────────────

it('admin can approve identity verification', function () {
    Storage::fake('private');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->withHeaders(asUser($user))
        ->postJson('/api/user/identity-verification', ['document' => $file]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->withHeaders(asUser($admin))
        ->putJson("/api/users/{$user->id}/identity-verification", ['status' => IdentityVerificationStatusEnum::APPROVED->value])
        ->assertOk();

    expect($user->fresh()->is_id_verified)->toBeTrue();
});

it('admin can reject identity verification', function () {
    Storage::fake('private');

    $user = User::factory()->create();
    $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

    $this->withHeaders(asUser($user))
        ->postJson('/api/user/identity-verification', ['document' => $file]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->withHeaders(asUser($admin))
        ->putJson("/api/users/{$user->id}/identity-verification", ['status' => IdentityVerificationStatusEnum::REJECTED->value])
        ->assertOk();

    expect($user->fresh()->is_id_verified)->toBeFalse();
});

it('non-admin cannot review identity verification', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->putJson("/api/users/{$other->id}/identity-verification", ['status' => IdentityVerificationStatusEnum::APPROVED->value])
        ->assertForbidden();
});

it('returns 404 when reviewing identity verification of unknown user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->withHeaders(asUser($admin))
        ->putJson('/api/users/00000000-0000-0000-0000-000000000000/identity-verification', ['status' => IdentityVerificationStatusEnum::APPROVED->value])
        ->assertNotFound();
});
