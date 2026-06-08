<?php

declare(strict_types=1);

use App\Enums\ActivityPermissionEnum;
use App\Enums\AvailabilityStatusEnum;
use App\Models\Activity;
use App\Models\ActivityAvailability;
use App\Models\User;

// ─── index (calendar) ─────────────────────────────────────────────────────────

it('manager can get the availability calendar', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->getJson("/api/activities/{$activity->id}/availabilities?month=2026-04")
        ->assertOk()
        ->assertJsonStructure(['data']);
});

it('admin can get the availability calendar', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser($admin))
        ->getJson("/api/activities/{$activity->id}/availabilities?month=2026-04")
        ->assertOk();
});

it('collaborator can get the availability calendar without specific permission', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    $activity->collaborators()->attach($collaborator->id);

    $this->withHeaders(asUser($collaborator))
        ->getJson("/api/activities/{$activity->id}/availabilities?month=2026-04")
        ->assertOk();
});

it('random user cannot get the availability calendar', function () {
    $activity = Activity::factory()->create();
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson("/api/activities/{$activity->id}/availabilities?month=2026-04")
        ->assertForbidden();
});

it('calendar month param is required', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->getJson("/api/activities/{$activity->id}/availabilities")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['month']);
});

it('calendar month param must use Y-m format', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->getJson("/api/activities/{$activity->id}/availabilities?month=2026-04-01")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['month']);
});

// ─── range ────────────────────────────────────────────────────────────────────

it('manager can get availabilities over a date range', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    ActivityAvailability::create([
        'activity_id' => $activity->id,
        'date' => '2026-04-02',
        'status' => AvailabilityStatusEnum::CLOSED->value,
        'note' => 'Fermé',
    ]);

    $response = $this->withHeaders(asUser($manager))
        ->getJson("/api/activities/{$activity->id}/availabilities/range?start_date=2026-04-01&end_date=2026-04-03")
        ->assertOk();

    expect($response->json('data'))->toHaveCount(3);
});

it('range returns OPEN status for dates not stored in the database', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $response = $this->withHeaders(asUser($manager))
        ->getJson("/api/activities/{$activity->id}/availabilities/range?start_date=2026-04-01&end_date=2026-04-01")
        ->assertOk();

    expect($response->json('data.0.status'))->toBe(AvailabilityStatusEnum::OPEN->value);
    expect($response->json('data.0.id'))->toBeNull();
});

it('range requires start_date and end_date', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->getJson("/api/activities/{$activity->id}/availabilities/range")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['start_date', 'end_date']);
});

// ─── store ────────────────────────────────────────────────────────────────────

it('manager can store availabilities over a period', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/activities/{$activity->id}/availabilities", [
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-03',
            'status' => AvailabilityStatusEnum::CLOSED->value,
        ])
        ->assertCreated();

    expect(ActivityAvailability::where('activity_id', $activity->id)->count())->toBe(3);
});

it('admin can store availabilities', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser($admin))
        ->postJson("/api/activities/{$activity->id}/availabilities", [
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-01',
            'status' => AvailabilityStatusEnum::OPEN->value,
        ])
        ->assertCreated();
});

it('collaborator with MANAGE_AVAILABILITIES can store availabilities', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    $activity->collaborators()->attach($collaborator->id);
    $activity->collaboratorPermissions()->create([
        'user_id' => $collaborator->id,
        'permission' => ActivityPermissionEnum::MANAGE_AVAILABILITIES->value,
    ]);

    $this->withHeaders(asUser($collaborator))
        ->postJson("/api/activities/{$activity->id}/availabilities", [
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-01',
            'status' => AvailabilityStatusEnum::OPEN->value,
        ])
        ->assertCreated();
});

it('collaborator without MANAGE_AVAILABILITIES cannot store availabilities', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    $activity->collaborators()->attach($collaborator->id);

    $this->withHeaders(asUser($collaborator))
        ->postJson("/api/activities/{$activity->id}/availabilities", [
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-01',
            'status' => AvailabilityStatusEnum::OPEN->value,
        ])
        ->assertForbidden();
});

it('end_date cannot be before start_date', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/activities/{$activity->id}/availabilities", [
            'start_date' => '2026-05-10',
            'end_date' => '2026-05-01',
            'status' => AvailabilityStatusEnum::CLOSED->value,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['end_date']);
});

it('date range cannot exceed 365 days', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/activities/{$activity->id}/availabilities", [
            'start_date' => '2026-01-01',
            'end_date' => '2027-01-10',
            'status' => AvailabilityStatusEnum::CLOSED->value,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['end_date']);
});

it('storing the same period twice upserts without error', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $payload = [
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-01',
        'status' => AvailabilityStatusEnum::CLOSED->value,
    ];

    $this->withHeaders(asUser($manager))
        ->postJson("/api/activities/{$activity->id}/availabilities", $payload)
        ->assertCreated();

    $this->withHeaders(asUser($manager))
        ->postJson("/api/activities/{$activity->id}/availabilities", array_merge($payload, ['status' => AvailabilityStatusEnum::OPEN->value]))
        ->assertCreated();

    expect(ActivityAvailability::where('activity_id', $activity->id)->count())->toBe(1);
    expect(ActivityAvailability::where('activity_id', $activity->id)->first()->status)->toBe(AvailabilityStatusEnum::OPEN);
});

// ─── bulk ─────────────────────────────────────────────────────────────────────

it('manager can bulk set availabilities', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/activities/{$activity->id}/availabilities/bulk", [
            'dates' => ['2026-07-01', '2026-07-04', '2026-07-07'],
            'status' => AvailabilityStatusEnum::CLOSED->value,
        ])
        ->assertOk();

    expect(ActivityAvailability::where('activity_id', $activity->id)->count())->toBe(3);
});

it('bulk cannot accept more than 365 dates', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $dates = collect(range(0, 365))->map(fn ($i) => now()->addDays($i)->toDateString())->all();

    $this->withHeaders(asUser($manager))
        ->postJson("/api/activities/{$activity->id}/availabilities/bulk", [
            'dates' => $dates,
            'status' => AvailabilityStatusEnum::CLOSED->value,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['dates']);
});

it('bulk requires valid date format in the dates array', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/activities/{$activity->id}/availabilities/bulk", [
            'dates' => ['not-a-date', '2026-07-04'],
            'status' => AvailabilityStatusEnum::CLOSED->value,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['dates.0']);
});

// ─── update ───────────────────────────────────────────────────────────────────

it('manager can update an availability', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $availability = ActivityAvailability::create([
        'activity_id' => $activity->id,
        'date' => '2026-08-01',
        'status' => AvailabilityStatusEnum::OPEN->value,
    ]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/availabilities/{$availability->id}", [
            'status' => AvailabilityStatusEnum::CLOSED->value,
            'note' => 'Vacances',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', AvailabilityStatusEnum::CLOSED->value)
        ->assertJsonPath('data.note', 'Vacances');
});

it('updating an availability from another activity returns 404', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $otherActivity = Activity::factory()->create(['manager_id' => $manager->id]);

    $availability = ActivityAvailability::create([
        'activity_id' => $otherActivity->id,
        'date' => '2026-08-01',
        'status' => AvailabilityStatusEnum::OPEN->value,
    ]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/availabilities/{$availability->id}", [
            'status' => AvailabilityStatusEnum::CLOSED->value,
        ])
        ->assertNotFound();
});

it('random user cannot update an availability', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $availability = ActivityAvailability::create([
        'activity_id' => $activity->id,
        'date' => '2026-08-01',
        'status' => AvailabilityStatusEnum::OPEN->value,
    ]);

    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->putJson("/api/activities/{$activity->id}/availabilities/{$availability->id}", [
            'status' => AvailabilityStatusEnum::CLOSED->value,
        ])
        ->assertForbidden();
});

// ─── destroy ──────────────────────────────────────────────────────────────────

it('manager can delete an availability', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $availability = ActivityAvailability::create([
        'activity_id' => $activity->id,
        'date' => '2026-09-01',
        'status' => AvailabilityStatusEnum::CLOSED->value,
    ]);

    $this->withHeaders(asUser($manager))
        ->deleteJson("/api/activities/{$activity->id}/availabilities/{$availability->id}")
        ->assertNoContent();

    expect(ActivityAvailability::find($availability->id))->toBeNull();
});

it('deleting an availability from another activity returns 404', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $otherActivity = Activity::factory()->create(['manager_id' => $manager->id]);

    $availability = ActivityAvailability::create([
        'activity_id' => $otherActivity->id,
        'date' => '2026-09-01',
        'status' => AvailabilityStatusEnum::CLOSED->value,
    ]);

    $this->withHeaders(asUser($manager))
        ->deleteJson("/api/activities/{$activity->id}/availabilities/{$availability->id}")
        ->assertNotFound();
});

it('random user cannot delete an availability', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $availability = ActivityAvailability::create([
        'activity_id' => $activity->id,
        'date' => '2026-09-01',
        'status' => AvailabilityStatusEnum::CLOSED->value,
    ]);

    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->deleteJson("/api/activities/{$activity->id}/availabilities/{$availability->id}")
        ->assertForbidden();
});
