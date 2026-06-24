<?php

declare(strict_types=1);

use App\Enums\ActivityPermissionEnum;
use App\Enums\WeekDayEnum;
use App\Models\Activity;
use App\Models\ActivityCycle;
use App\Models\AnimalType;
use App\Models\User;

// ─── index ────────────────────────────────────────────────────────────────────

it('manager can list cycles', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    ActivityCycle::factory()->create(['activity_id' => $activity->id]);

    $this->withHeaders(asUser($manager))
        ->getJson("/api/activities/{$activity->id}/cycles")
        ->assertOk()
        ->assertJsonStructure(['data']);
});

it('random user cannot list cycles', function () {
    $activity = Activity::factory()->create();
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson("/api/activities/{$activity->id}/cycles")
        ->assertForbidden();
});

// ─── store ────────────────────────────────────────────────────────────────────

it('manager can create a cycle', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->postJson("/api/activities/{$activity->id}/cycles", [
            'start_date' => '2026-06-01',
            'end_date' => '2026-08-31',
            'priority' => 5,
            'is_active' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.priority', 5);

    $this->assertDatabaseHas('activities_cycles', [
        'activity_id' => $activity->id,
        'priority' => 5,
    ]);
});

it('collaborator with MANAGE_CYCLES can create a cycle', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    attachCollaborator($activity, $collaborator, [ActivityPermissionEnum::MANAGE_CYCLES]);

    $this->withHeaders(asUser($collaborator))
        ->postJson("/api/activities/{$activity->id}/cycles", ['priority' => 1])
        ->assertCreated();
});

it('collaborator without MANAGE_CYCLES cannot create a cycle', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    attachCollaborator($activity, $collaborator);

    $this->withHeaders(asUser($collaborator))
        ->postJson("/api/activities/{$activity->id}/cycles", ['priority' => 1])
        ->assertForbidden();
});

// ─── update ───────────────────────────────────────────────────────────────────

it('manager can update a cycle', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $cycle = ActivityCycle::factory()->create(['activity_id' => $activity->id]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/cycles/{$cycle->id}", ['is_active' => false])
        ->assertOk()
        ->assertJsonPath('data.is_active', false);
});

it('returns 404 when updating a cycle of another activity', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $otherCycle = ActivityCycle::factory()->create();

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/cycles/{$otherCycle->id}", ['priority' => 9])
        ->assertNotFound();
});

// ─── destroy ──────────────────────────────────────────────────────────────────

it('manager can delete a cycle', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $cycle = ActivityCycle::factory()->create(['activity_id' => $activity->id]);

    $this->withHeaders(asUser($manager))
        ->deleteJson("/api/activities/{$activity->id}/cycles/{$cycle->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('activities_cycles', ['id' => $cycle->id]);
});

// ─── settings ─────────────────────────────────────────────────────────────────

it('manager can upsert cycle settings', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $cycle = ActivityCycle::factory()->create(['activity_id' => $activity->id]);
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/cycles/{$cycle->id}/settings", [
            'settings' => [
                [
                    'animal_type_id' => $animalType->id,
                    'max_capacity' => 12,
                    'price' => 30.50,
                    'sum_weekdays' => WeekDayEnum::ALL,
                ],
            ],
        ])
        ->assertOk();

    $this->assertDatabaseHas('activities_cycles_settings', [
        'activity_cycle_id' => $cycle->id,
        'animal_type_id' => $animalType->id,
        'max_capacity' => 12,
        'sum_weekdays' => WeekDayEnum::ALL,
    ]);
});

it('upsert settings replaces previous settings', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $cycle = ActivityCycle::factory()->create(['activity_id' => $activity->id]);
    $animalType = AnimalType::create(['code' => 'cat', 'name' => 'Chat', 'category' => 'mammals']);

    $cycle->settings()->create([
        'animal_type_id' => $animalType->id,
        'max_capacity' => 5,
        'price' => 20.00,
        'sum_weekdays' => WeekDayEnum::ALL,
    ]);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/cycles/{$cycle->id}/settings", [
            'settings' => [
                ['animal_type_id' => $animalType->id, 'max_capacity' => 8, 'price' => 22.00],
            ],
        ])
        ->assertOk();

    expect($cycle->settings()->count())->toBe(1);
    $this->assertDatabaseHas('activities_cycles_settings', [
        'activity_cycle_id' => $cycle->id,
        'max_capacity' => 8,
    ]);
});

it('rejects settings with invalid sum_weekdays', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $cycle = ActivityCycle::factory()->create(['activity_id' => $activity->id]);
    $animalType = AnimalType::create(['code' => 'bird', 'name' => 'Oiseau', 'category' => 'birds']);

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/cycles/{$cycle->id}/settings", [
            'settings' => [
                ['animal_type_id' => $animalType->id, 'max_capacity' => 8, 'price' => 22.00, 'sum_weekdays' => 200],
            ],
        ])
        ->assertUnprocessable();
});

// ─── closed week days ───────────────────────────────────────────────────────────

it('manager can upsert closed week days', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $cycle = ActivityCycle::factory()->create(['activity_id' => $activity->id]);

    $sundayAndSaturday = WeekDayEnum::SATURDAY->value | WeekDayEnum::SUNDAY->value;

    $this->withHeaders(asUser($manager))
        ->putJson("/api/activities/{$activity->id}/cycles/{$cycle->id}/closed-week-days", [
            'sum_weekdays' => $sundayAndSaturday,
        ])
        ->assertOk();

    $this->assertDatabaseHas('activities_cycles_closed_week_days', [
        'activity_cycle_id' => $cycle->id,
        'sum_weekdays' => $sundayAndSaturday,
    ]);
});
