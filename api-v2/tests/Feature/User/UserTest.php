<?php

declare(strict_types=1);

use App\Enums\OrganizationMemberStatusEnum;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

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
        ->assertNoContent();
});

it('rejects password change with wrong current password', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->putJson('/api/user/password', [
            'current_password' => 'wrong-password',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);
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

// ─── Account deletion (self) ──────────────────────────────────────────────────

it('user can delete their own account', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->deleteJson('/api/user')
        ->assertNoContent();

    expect(User::withTrashed()->find($user->id)->deleted_at)->not->toBeNull();
});

// ─── Access to the management area ───────────────────────────────────────────

function organizationWithMember(User $member, OrganizationMemberStatusEnum $status): Organization
{
    $organization = Organization::create([
        'owner_id' => $member->id,
        'legal_name' => 'Pension des Lilas',
        'legal_form' => 'company',
    ]);

    OrganizationMember::create([
        'organization_id' => $organization->id,
        'user_id' => $member->id,
        'status' => $status,
    ]);

    return $organization;
}

it('denies the management area to a user who belongs to no organization', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('data.can_access_management', false);
});

it('grants the management area to an active member of an organization', function () {
    $user = User::factory()->create();
    organizationWithMember($user, OrganizationMemberStatusEnum::ACTIVE);

    $this->withHeaders(asUser($user))
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('data.can_access_management', true);
});

it('authorizes the access-management ability only for active organization members', function () {
    $member = User::factory()->create();
    $outsider = User::factory()->create();
    organizationWithMember($member, OrganizationMemberStatusEnum::ACTIVE);

    expect(Gate::forUser($member)->allows('access-management'))->toBeTrue()
        ->and(Gate::forUser($outsider)->allows('access-management'))->toBeFalse();
});

it('keeps the management area closed while an invitation is pending', function () {
    $user = User::factory()->create();
    organizationWithMember($user, OrganizationMemberStatusEnum::PENDING);

    $this->withHeaders(asUser($user))
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('data.can_access_management', false);
});
