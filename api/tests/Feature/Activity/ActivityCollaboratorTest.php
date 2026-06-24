<?php

declare(strict_types=1);

use App\Enums\CollaboratorStatusEnum;
use App\Models\Activity;
use App\Models\ActivityCollaborator;
use App\Models\ActivityRole;
use App\Models\User;

function pendingLink(Activity $activity, User $user): void
{
    $activity->collaboratorLinks()->create([
        'user_id' => $user->id,
        'status' => CollaboratorStatusEnum::PENDING->value,
        'invited_at' => now(),
    ]);
}

function acceptedLink(Activity $activity, User $user): void
{
    $activity->collaboratorLinks()->create([
        'user_id' => $user->id,
        'status' => CollaboratorStatusEnum::ACCEPTED->value,
        'invited_at' => now(),
        'responded_at' => now(),
    ]);
}

// ─── index ──────────────────────────────────────────────────────────────────────

it('manager can list collaborators with their status', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    pendingLink($activity, User::factory()->create());
    acceptedLink($activity, User::factory()->create());

    $this->withHeaders(asUser($manager))
        ->getJson("/api/activities/{$activity->id}/collaborators")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('non-manager cannot list collaborators', function () {
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser(User::factory()->create()))
        ->getJson("/api/activities/{$activity->id}/collaborators")
        ->assertForbidden();
});

// ─── invite ─────────────────────────────────────────────────────────────────────

it('manager can invite an existing user by email', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $invitee = User::factory()->create();

    $this->withHeaders(asUser($manager))
        ->postJson("/api/activities/{$activity->id}/collaborators", ['email' => $invitee->email])
        ->assertCreated()
        ->assertJsonPath('data.status', CollaboratorStatusEnum::PENDING->value)
        ->assertJsonPath('data.user.id', $invitee->id);
});

it('inviting an unknown email returns 422', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/activities/{$activity->id}/collaborators", ['email' => 'ghost@example.com'])
        ->assertUnprocessable();
});

it('inviting a user who already has a pending invitation returns 422', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $invitee = User::factory()->create();
    pendingLink($activity, $invitee);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/activities/{$activity->id}/collaborators", ['email' => $invitee->email])
        ->assertUnprocessable();
});

it('inviting the manager themselves returns 422', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/activities/{$activity->id}/collaborators", ['email' => $manager->email])
        ->assertUnprocessable();
});

it('can re-invite a user who previously refused', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $invitee = User::factory()->create();
    $activity->collaboratorLinks()->create([
        'user_id' => $invitee->id,
        'status' => CollaboratorStatusEnum::REFUSED->value,
        'invited_at' => now(),
        'responded_at' => now(),
    ]);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/activities/{$activity->id}/collaborators", ['email' => $invitee->email])
        ->assertCreated()
        ->assertJsonPath('data.status', CollaboratorStatusEnum::PENDING->value);
});

it('non-manager cannot invite a collaborator', function () {
    $activity = Activity::factory()->create();
    $invitee = User::factory()->create();

    $this->withHeaders(asUser(User::factory()->create()))
        ->postJson("/api/activities/{$activity->id}/collaborators", ['email' => $invitee->email])
        ->assertForbidden();
});

// ─── invitee accept / decline ─────────────────────────────────────────────────

it('invitee can list their pending invitations', function () {
    $activity = Activity::factory()->create();
    $invitee = User::factory()->create();
    pendingLink($activity, $invitee);

    $this->withHeaders(asUser($invitee))
        ->getJson('/api/collaborator-invitations')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('invitee can accept an invitation', function () {
    $activity = Activity::factory()->create();
    $invitee = User::factory()->create();
    pendingLink($activity, $invitee);

    $this->withHeaders(asUser($invitee))
        ->putJson("/api/activities/{$activity->id}/collaborators/accept")
        ->assertOk()
        ->assertJsonPath('data.status', CollaboratorStatusEnum::ACCEPTED->value);

    $link = ActivityCollaborator::query()
        ->where('activity_id', $activity->id)
        ->where('user_id', $invitee->id)
        ->firstOrFail();
    expect($link->status)->toBe(CollaboratorStatusEnum::ACCEPTED);
});

it('invitee can decline an invitation', function () {
    $activity = Activity::factory()->create();
    $invitee = User::factory()->create();
    pendingLink($activity, $invitee);

    $this->withHeaders(asUser($invitee))
        ->putJson("/api/activities/{$activity->id}/collaborators/decline")
        ->assertOk()
        ->assertJsonPath('data.status', CollaboratorStatusEnum::REFUSED->value);
});

it('accepting without a pending invitation returns 404', function () {
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser(User::factory()->create()))
        ->putJson("/api/activities/{$activity->id}/collaborators/accept")
        ->assertNotFound();
});

// ─── assign role ────────────────────────────────────────────────────────────────

it('manager can assign a role to an accepted collaborator', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $collaborator = User::factory()->create();
    acceptedLink($activity, $collaborator);
    $role = ActivityRole::factory()->create(['activity_id' => $activity->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/collaborators/{$collaborator->id}/role", ['role_id' => $role->id])
        ->assertOk()
        ->assertJsonPath('data.role.id', $role->id);
});

it('assigning a role to a pending collaborator returns 422', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $collaborator = User::factory()->create();
    pendingLink($activity, $collaborator);
    $role = ActivityRole::factory()->create(['activity_id' => $activity->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/collaborators/{$collaborator->id}/role", ['role_id' => $role->id])
        ->assertUnprocessable();
});

it('assigning a role from another activity returns 404', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $collaborator = User::factory()->create();
    acceptedLink($activity, $collaborator);
    $role = ActivityRole::factory()->create(['activity_id' => Activity::factory()->create()->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/collaborators/{$collaborator->id}/role", ['role_id' => $role->id])
        ->assertNotFound();
});

// ─── remove ─────────────────────────────────────────────────────────────────────

it('manager can remove a collaborator', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $collaborator = User::factory()->create();
    acceptedLink($activity, $collaborator);

    $this->withHeaders(asUser($manager))
        ->deleteJson("/api/activities/{$activity->id}/collaborators/{$collaborator->id}")
        ->assertNoContent();

    expect($activity->collaboratorLinks()->where('user_id', $collaborator->id)->exists())->toBeFalse();
});
