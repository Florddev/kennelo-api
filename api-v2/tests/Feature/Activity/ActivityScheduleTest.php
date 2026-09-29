<?php

declare(strict_types=1);

use App\Enums\AvailabilityStatusEnum;
use App\Enums\OrganizationRoleEnum;
use App\Enums\WeekDayEnum;
use App\Models\Activity;
use App\Models\ActivityAvailability;

describe('opening hours', function () {
    it('replaces the whole week', function () {
        $activity = Activity::factory()->create();
        $activity->openingHours()->create(['weekday' => WeekDayEnum::SUNDAY, 'opens_at' => '10:00', 'closes_at' => '12:00']);

        $this->withHeaders(asUser($activity->organization->owner))
            ->putJson("/api/activities/{$activity->id}/opening-hours", ['hours' => [
                ['weekday' => WeekDayEnum::MONDAY->value, 'opens_at' => '14:00', 'closes_at' => '18:00'],
                ['weekday' => WeekDayEnum::MONDAY->value, 'opens_at' => '09:00', 'closes_at' => '12:00'],
            ]])
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0', ['weekday' => 1, 'opens_at' => '09:00', 'closes_at' => '12:00']);

        expect($activity->openingHours()->where('weekday', WeekDayEnum::SUNDAY)->exists())->toBeFalse();
    });

    it('refuses overlapping ranges on the same day', function () {
        $activity = Activity::factory()->create();

        $this->withHeaders(asUser($activity->organization->owner))
            ->putJson("/api/activities/{$activity->id}/opening-hours", ['hours' => [
                ['weekday' => 1, 'opens_at' => '09:00', 'closes_at' => '13:00'],
                ['weekday' => 1, 'opens_at' => '12:00', 'closes_at' => '18:00'],
                ['weekday' => 2, 'opens_at' => '12:00', 'closes_at' => '18:00'],
            ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['hours' => __('activity.opening_hours_overlap')]);
    });

    it('refuses a range that closes before it opens', function () {
        $activity = Activity::factory()->create();

        $this->withHeaders(asUser($activity->organization->owner))
            ->putJson("/api/activities/{$activity->id}/opening-hours", ['hours' => [
                ['weekday' => 1, 'opens_at' => '18:00', 'closes_at' => '09:00'],
            ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['hours.0.closes_at']);
    });

    it('forbids an employee', function () {
        $activity = Activity::factory()->create();

        $this->withHeaders(asUser(memberOf($activity->organization, OrganizationRoleEnum::EMPLOYEE, $activity->id)))
            ->putJson("/api/activities/{$activity->id}/opening-hours", ['hours' => []])
            ->assertForbidden();
    });
});

describe('exceptions', function () {
    it('closes every day of a period', function () {
        $activity = Activity::factory()->create();

        $this->withHeaders(asUser($activity->organization->owner))
            ->postJson("/api/activities/{$activity->id}/availabilities", [
                'from' => '2026-12-24',
                'to' => '2026-12-26',
                'status' => 'closed',
                'note' => 'Noël',
            ])
            ->assertOk()
            ->assertJsonCount(3)
            ->assertJsonPath('*.date', ['2026-12-24', '2026-12-25', '2026-12-26'])
            ->assertJsonPath('0.status', 'closed');
    });

    it('replaces the exception already set on a date', function () {
        $activity = Activity::factory()->create();
        $headers = asUser($activity->organization->owner);

        $this->withHeaders($headers)->postJson("/api/activities/{$activity->id}/availabilities", ['dates' => ['2026-12-25'], 'status' => 'closed']);
        $this->withHeaders($headers)
            ->postJson("/api/activities/{$activity->id}/availabilities", ['dates' => ['2026-12-25', '2026-12-31'], 'status' => 'open'])
            ->assertOk()
            ->assertJsonCount(2);

        expect(ActivityAvailability::query()->count())->toBe(2)
            ->and($activity->availabilities()->whereDate('date', '2026-12-25')->value('status'))->toBe(AvailabilityStatusEnum::OPEN);
    });

    it('refuses dates and a period together, and a period longer than a year', function () {
        $activity = Activity::factory()->create();
        $headers = asUser($activity->organization->owner);

        $this->withHeaders($headers)
            ->postJson("/api/activities/{$activity->id}/availabilities", ['dates' => ['2026-12-25'], 'from' => '2026-12-24', 'to' => '2026-12-26', 'status' => 'closed'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dates']);

        $this->withHeaders($headers)
            ->postJson("/api/activities/{$activity->id}/availabilities", ['from' => '2026-01-01', 'to' => '2027-06-01', 'status' => 'closed'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
    });

    it('lists the exceptions of a period', function () {
        $activity = Activity::factory()->create();
        $headers = asUser($activity->organization->owner);
        $this->withHeaders($headers)->postJson("/api/activities/{$activity->id}/availabilities", ['dates' => ['2026-11-30', '2026-12-25'], 'status' => 'closed']);

        $this->withHeaders($headers)
            ->getJson("/api/activities/{$activity->id}/availabilities?from=2026-12-01&to=2026-12-31")
            ->assertOk()
            ->assertJsonPath('*.date', ['2026-12-25']);
    });

    it('removes an exception of the activity only', function () {
        $activity = Activity::factory()->create();
        $other = Activity::factory()->create();
        $this->withHeaders(asUser($other->organization->owner))->postJson("/api/activities/{$other->id}/availabilities", ['dates' => ['2026-12-25'], 'status' => 'closed']);
        $foreign = $other->availabilities()->sole();

        $this->withHeaders(asUser($activity->organization->owner))
            ->deleteJson("/api/activities/{$activity->id}/availabilities/{$foreign->id}")
            ->assertNotFound();

        $this->withHeaders(asUser($other->organization->owner))
            ->deleteJson("/api/activities/{$other->id}/availabilities/{$foreign->id}")
            ->assertNoContent();
    });
});
