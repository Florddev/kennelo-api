<?php

declare(strict_types=1);

use App\Enums\ActivityPermissionEnum;
use App\Enums\CollaboratorStatusEnum;
use App\Models\Activity;
use App\Models\User;

// ─── index ────────────────────────────────────────────────────────────────────

it('authenticated user can list activities', function () {
    $user = User::factory()->create();
    Activity::factory()->count(3)->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/activities')
        ->assertOk()
        ->assertJsonStructure(['data']);
});

it('unauthenticated user cannot list activities', function () {
    $this->getJson('/api/activities')
        ->assertUnauthorized();
});

it('accepted collaborator sees the collaborated activity in their activities list', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $collaborator = User::factory()->create();
    attachCollaborator($activity, $collaborator);

    $this->withHeaders(asUser($collaborator))
        ->getJson('/api/activities')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $activity->id);
});

it('pending collaborator does not see the activity in their activities list', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $collaborator = User::factory()->create();
    attachCollaborator($activity, $collaborator, [], CollaboratorStatusEnum::PENDING);

    $this->withHeaders(asUser($collaborator))
        ->getJson('/api/activities')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

// ─── show ─────────────────────────────────────────────────────────────────────

it('any authenticated user can view an activity', function () {
    $user = User::factory()->create();
    $activity = Activity::factory()->create(['is_active' => true]);

    $this->withHeaders(asUser($user))
        ->getJson("/api/activities/{$activity->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $activity->id);
});

it('returns 404 for unknown activity', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson('/api/activities/00000000-0000-0000-0000-000000000000')
        ->assertNotFound();
});

it('third party cannot view an activity whose manager email is unverified', function () {
    $manager = User::factory()->unverified()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $visitor = User::factory()->create();

    $this->withHeaders(asUser($visitor))
        ->getJson("/api/activities/{$activity->id}")
        ->assertNotFound();
});

it('owner can view their own activity even if their email is unverified', function () {
    $manager = User::factory()->unverified()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->getJson("/api/activities/{$activity->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $activity->id);
});

it('collaborator can view an activity whose manager email is unverified', function () {
    $manager = User::factory()->unverified()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $collaborator = User::factory()->create();
    attachCollaborator($activity, $collaborator);

    $this->withHeaders(asUser($collaborator))
        ->getJson("/api/activities/{$activity->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $activity->id);
});

it('admin can view an activity whose manager email is unverified', function () {
    $manager = User::factory()->unverified()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->withHeaders(asUser($admin))
        ->getJson("/api/activities/{$activity->id}")
        ->assertOk();
});

// ─── store ────────────────────────────────────────────────────────────────────

it('manager can create an activity', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->withHeaders(asUser($manager))
        ->postJson('/api/activities', ['name' => 'Mon Chenil'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Mon Chenil');
});

it('admin can create an activity', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->withHeaders(asUser($admin))
        ->postJson('/api/activities', ['name' => 'Chenil Admin'])
        ->assertCreated();
});

it('regular verified user without role can create an activity and becomes a manager', function () {
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->postJson('/api/activities', ['name' => 'Mon Chenil'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Mon Chenil');

    expect($user->fresh()->hasRole('manager'))->toBeTrue();
});

it('user with unverified email cannot create an activity', function () {
    $user = User::factory()->unverified()->create();

    $this->withHeaders(asUser($user))
        ->postJson('/api/activities', ['name' => 'Mon Chenil'])
        ->assertForbidden();
});

it('unauthenticated user cannot create an activity', function () {
    $this->postJson('/api/activities', ['name' => 'Mon Chenil'])
        ->assertUnauthorized();
});

it('activity creation requires a name', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->withHeaders(asUser($manager))
        ->postJson('/api/activities', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('activity creation accepts an ISO alpha-2 address country', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->withHeaders(asUser($manager))
        ->postJson('/api/activities', [
            'name' => 'Chenil Adresse',
            'address' => [
                'line1' => '1 Rue de Test',
                'city' => 'Paris',
                'postal_code' => '75001',
                'country' => 'FR',
            ],
        ])
        ->assertCreated();
});

it('activity creation rejects a full country name in the address', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');

    $this->withHeaders(asUser($manager))
        ->postJson('/api/activities', [
            'name' => 'Chenil Adresse',
            'address' => [
                'line1' => '1 Rue de Test',
                'city' => 'Paris',
                'postal_code' => '75001',
                'country' => 'France',
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['address.country']);
});

// ─── update ───────────────────────────────────────────────────────────────────

it('manager (owner) can update their activity', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}", ['name' => 'Nouveau Nom'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Nouveau Nom');
});

it('admin can update any activity', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser($admin))
        ->putJson("/api/activities/{$activity->id}", ['name' => 'Modifié par admin'])
        ->assertOk();
});

it('collaborator with UPDATE_ACTIVITY can update', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    attachCollaborator($activity, $collaborator, [ActivityPermissionEnum::UPDATE_ACTIVITY]);

    $this->withHeaders(asUser($collaborator))
        ->putJson("/api/activities/{$activity->id}", ['name' => 'Modifié par collab'])
        ->assertOk();
});

it('random user cannot update an activity', function () {
    $activity = Activity::factory()->create();
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->putJson("/api/activities/{$activity->id}", ['name' => 'Hack'])
        ->assertForbidden();
});

it('collaborator without UPDATE_ACTIVITY cannot update', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    attachCollaborator($activity, $collaborator);

    $this->withHeaders(asUser($collaborator))
        ->putJson("/api/activities/{$activity->id}", ['name' => 'Hack'])
        ->assertForbidden();
});

// ─── destroy ──────────────────────────────────────────────────────────────────

it('manager can delete their activity', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->deleteJson("/api/activities/{$activity->id}")
        ->assertNoContent();

    expect(Activity::withTrashed()->find($activity->id)->deleted_at)->not->toBeNull();
});

it('admin can delete any activity', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser($admin))
        ->deleteJson("/api/activities/{$activity->id}")
        ->assertNoContent();
});

it('collaborator cannot delete an activity', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    attachCollaborator($activity, $collaborator);

    $this->withHeaders(asUser($collaborator))
        ->deleteJson("/api/activities/{$activity->id}")
        ->assertForbidden();
});

it('random user cannot delete an activity', function () {
    $activity = Activity::factory()->create();
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->deleteJson("/api/activities/{$activity->id}")
        ->assertForbidden();
});
