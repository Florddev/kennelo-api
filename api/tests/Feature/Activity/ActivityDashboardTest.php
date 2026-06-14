<?php

declare(strict_types=1);

use App\Models\Activity;
use App\Models\ActivityCycle;
use App\Models\ActivityCycleSetting;
use App\Models\AnimalType;
use App\Models\User;

// ─── show ─────────────────────────────────────────────────────────────────────

it('manager can view the dashboard', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $this->withHeaders(asUser($manager))
        ->getJson("/api/activities/{$activity->id}/dashboard")
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'summary' => ['total_capacity', 'occupied_spots', 'available_spots', 'today_status'],
                'occupancy_by_animal',
                'upcoming_bookings',
            ],
        ]);
});

it('admin can view the dashboard', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $activity = Activity::factory()->create();

    $this->withHeaders(asUser($admin))
        ->getJson("/api/activities/{$activity->id}/dashboard")
        ->assertOk();
});

it('collaborator can view the dashboard without specific permission', function () {
    $manager = User::factory()->create();
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $collaborator = User::factory()->create();
    attachCollaborator($activity, $collaborator);

    $this->withHeaders(asUser($collaborator))
        ->getJson("/api/activities/{$activity->id}/dashboard")
        ->assertOk();
});

it('random user cannot view the dashboard', function () {
    $activity = Activity::factory()->create();
    $user = User::factory()->create();

    $this->withHeaders(asUser($user))
        ->getJson("/api/activities/{$activity->id}/dashboard")
        ->assertForbidden();
});

it('unauthenticated user cannot view the dashboard', function () {
    $activity = Activity::factory()->create();

    $this->getJson("/api/activities/{$activity->id}/dashboard")
        ->assertUnauthorized();
});

it('dashboard summary shows zero capacity when no capacities are configured', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);

    $response = $this->withHeaders(asUser($manager))
        ->getJson("/api/activities/{$activity->id}/dashboard")
        ->assertOk();

    expect($response->json('data.summary.total_capacity'))->toBe(0);
    expect($response->json('data.occupancy_by_animal'))->toBeEmpty();
    expect($response->json('data.upcoming_bookings'))->toBeEmpty();
});

it('dashboard summary reflects configured capacities', function () {
    $manager = User::factory()->create();
    $manager->assignRole('manager');
    $activity = Activity::factory()->create(['manager_id' => $manager->id]);
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);

    $cycle = ActivityCycle::create([
        'activity_id' => $activity->id,
        'priority' => 0,
        'is_active' => true,
    ]);

    ActivityCycleSetting::create([
        'activity_cycle_id' => $cycle->id,
        'animal_type_id' => $animalType->id,
        'max_capacity' => 10,
    ]);

    $response = $this->withHeaders(asUser($manager))
        ->getJson("/api/activities/{$activity->id}/dashboard")
        ->assertOk();

    expect($response->json('data.summary.total_capacity'))->toBe(10);
    expect($response->json('data.summary.occupied_spots'))->toBe(0);
    expect($response->json('data.summary.available_spots'))->toBe(10);
    expect($response->json('data.occupancy_by_animal'))->toHaveCount(1);
});
