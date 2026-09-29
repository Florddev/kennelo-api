<?php

declare(strict_types=1);

use App\Enums\OrganizationRoleEnum;
use App\Enums\WeekDayEnum;
use App\Models\Activity;
use App\Models\AgendaResource;
use App\Models\Booking;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\ResourceBooking;
use Carbon\CarbonImmutable;

describe('resources', function () {
    it('creates a person of the team, then an equipment', function () {
        $organization = Organization::factory()->create();
        $member = OrganizationMember::factory()->for($organization)->create();

        $this->withHeaders(asUser($organization->owner))
            ->postJson("/api/organizations/{$organization->id}/resources", [
                'type' => 'staff',
                'name' => 'Léa',
                'organization_member_id' => $member->id,
            ])
            ->assertCreated()
            ->assertJsonPath('type', 'staff')
            ->assertJsonPath('is_active', true)
            ->assertJsonPath('member.user_id', $member->user_id);

        $this->postJson("/api/organizations/{$organization->id}/resources", ['type' => 'equipment', 'name' => 'Table 1'])
            ->assertCreated();

        $members = collect($this->getJson("/api/organizations/{$organization->id}/members")->assertOk()->json());

        expect($members->firstWhere('id', $member->id)['resource_id'])->toBe(AgendaResource::query()->where('organization_member_id', $member->id)->value('id'))
            ->and($members->firstWhere('user.id', $organization->owner_id)['resource_id'])->toBeNull();
    });

    it('links a person to one active member of the organization, once', function () {
        $organization = Organization::factory()->create();
        $member = OrganizationMember::factory()->for($organization)->create();
        AgendaResource::factory()->staff($member)->create();
        $invited = OrganizationMember::factory()->for($organization)->pending()->create();
        $stranger = OrganizationMember::factory()->create();

        $this->withHeaders(asUser($organization->owner));

        foreach ([$member, $invited, $stranger] as $candidate) {
            $this->postJson("/api/organizations/{$organization->id}/resources", ['type' => 'staff', 'name' => 'X', 'organization_member_id' => $candidate->id])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['organization_member_id']);
        }

        $this->postJson("/api/organizations/{$organization->id}/resources", ['type' => 'space', 'name' => 'Salle', 'organization_member_id' => $member->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['organization_member_id']);
    });

    it('lets only catalog managers manage the resources', function () {
        $organization = Organization::factory()->create();
        $resource = AgendaResource::factory()->for($organization)->create();
        $employee = memberOf($organization, OrganizationRoleEnum::EMPLOYEE, Activity::factory()->for($organization)->create()->id);

        $this->withHeaders(asUser($employee))
            ->getJson("/api/organizations/{$organization->id}/resources")
            ->assertOk()
            ->assertJsonCount(1);

        $this->patchJson("/api/organizations/{$organization->id}/resources/{$resource->id}", ['name' => 'X'])->assertForbidden();

        $this->withHeaders(asUser(memberOf(Organization::factory()->create())))
            ->getJson("/api/organizations/{$organization->id}/resources")
            ->assertNotFound();
    });

    it('deletes an unused resource, and keeps one that had appointments', function () {
        $salon = appointmentSalon();
        $table = AgendaResource::factory()->for($salon['activity']->organization)->create();
        ResourceBooking::factory()->for($table, 'resource')->create();
        Booking::factory()->appointment($salon, now()->addDays(2)->toIso8601String())->create();
        $organization = $salon['activity']->organization;

        $this->withHeaders(asUser($organization->owner))
            ->deleteJson("/api/organizations/{$organization->id}/resources/{$table->id}")
            ->assertNoContent();

        $this->deleteJson("/api/organizations/{$organization->id}/resources/{$salon['resource']->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['resource' => __('agenda.resource_in_use')]);

        $this->patchJson("/api/organizations/{$organization->id}/resources/{$salon['resource']->id}", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('is_active', false);

        expect(AgendaResource::find($table->id))->toBeNull()
            ->and(ResourceBooking::count())->toBe(1);
    });
});

describe('schedules', function () {
    it('replaces the week of a resource in an activity, leaving the other activities alone', function () {
        $salon = appointmentSalon();
        $activity = $salon['activity'];
        $other = Activity::factory()->for($activity->organization)->create();
        $lea = $salon['resource'];
        $lea->schedules()->create(['organization_id' => $lea->organization_id, 'activity_id' => $other->id, 'weekday' => WeekDayEnum::SUNDAY, 'start_time' => '10:00', 'end_time' => '12:00']);

        $this->withHeaders(asUser($activity->organization->owner))
            ->putJson("/api/activities/{$activity->id}/resources/{$lea->id}/schedules", ['schedules' => [
                ['weekday' => WeekDayEnum::MONDAY->value, 'start_time' => '09:00', 'end_time' => '12:00'],
                ['weekday' => WeekDayEnum::MONDAY->value, 'start_time' => '14:00', 'end_time' => '18:00'],
            ]])
            ->assertOk()
            ->assertJsonCount(3, 'schedules')
            ->assertJsonPath('schedules.0.start_time', '09:00');

        expect($lea->schedules()->where('activity_id', $activity->id)->count())->toBe(2)
            ->and($lea->schedules()->where('activity_id', $other->id)->count())->toBe(1);
    });

    it('refuses two overlapping ranges of a day', function () {
        $salon = appointmentSalon();

        $this->withHeaders(asUser($salon['activity']->organization->owner))
            ->putJson("/api/activities/{$salon['activity']->id}/resources/{$salon['resource']->id}/schedules", ['schedules' => [
                ['weekday' => WeekDayEnum::MONDAY->value, 'start_time' => '09:00', 'end_time' => '12:00'],
                ['weekday' => WeekDayEnum::MONDAY->value, 'start_time' => '11:00', 'end_time' => '13:00'],
            ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['schedules' => __('agenda.schedule_overlap')]);
    });

    it('lets an activity manager plan the resources of the organization in their activity only', function () {
        $salon = appointmentSalon();
        $activity = $salon['activity'];
        $manager = memberOf($activity->organization, OrganizationRoleEnum::ACTIVITY_MANAGER, $activity->id);
        $other = Activity::factory()->for($activity->organization)->create();
        $foreign = AgendaResource::factory()->create();

        $this->withHeaders(asUser($manager))
            ->putJson("/api/activities/{$activity->id}/resources/{$salon['resource']->id}/schedules", ['schedules' => []])
            ->assertOk()
            ->assertJsonCount(0, 'schedules');

        $this->putJson("/api/activities/{$other->id}/resources/{$salon['resource']->id}/schedules", ['schedules' => []])
            ->assertForbidden();

        $this->putJson("/api/activities/{$activity->id}/resources/{$foreign->id}/schedules", ['schedules' => []])
            ->assertNotFound();
    });
});

describe('leaving the team', function () {
    it('removes the resource of a member who never had appointments', function () {
        $salon = appointmentSalon();
        $organization = $salon['activity']->organization;

        $this->withHeaders(asUser($organization->owner))
            ->deleteJson("/api/organizations/{$organization->id}/members/{$salon['resource']->organization_member_id}")
            ->assertNoContent();

        expect(AgendaResource::count())->toBe(0);
    });

    it('keeps the history of a member who had appointments, and waits for the upcoming ones', function () {
        $salon = appointmentSalon();
        $organization = $salon['activity']->organization;
        $lea = $salon['resource'];
        $past = Booking::factory()->appointment($salon, now()->subDays(3)->toIso8601String())->create();
        $upcoming = Booking::factory()->appointment($salon, now()->addDays(3)->toIso8601String())->create();
        $this->withHeaders(asUser($organization->owner));

        $this->deleteJson("/api/organizations/{$organization->id}/members/{$lea->organization_member_id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['member' => __('agenda.member_has_appointments')]);

        $upcoming->items()->sole()->resourceBooking()->delete();

        $this->deleteJson("/api/organizations/{$organization->id}/members/{$lea->organization_member_id}")
            ->assertNoContent();

        $lea->refresh();

        expect($lea->organization_member_id)->toBeNull()
            ->and($lea->is_active)->toBeFalse()
            ->and($past->items()->sole()->resourceBooking)->not->toBeNull();
    });
});

describe('absences', function () {
    it('lets a person add their own absence, and a manager any', function () {
        $salon = appointmentSalon();
        $lea = $salon['resource'];
        $table = AgendaResource::factory()->for($salon['activity']->organization)->create();
        $body = ['starts_at' => '2026-12-24T00:00:00+01:00', 'ends_at' => '2026-12-27T00:00:00+01:00', 'note' => 'Noël'];

        $this->withHeaders(asUser($lea->member->user))
            ->postJson("/api/organizations/{$lea->organization_id}/resources/{$lea->id}/absences", $body)
            ->assertCreated()
            ->assertJsonPath('kind', 'absence')
            ->assertJsonPath('starts_at', '2026-12-23T23:00:00.000000Z')
            ->assertJsonPath('note', 'Noël');

        $this->postJson("/api/organizations/{$lea->organization_id}/resources/{$table->id}/absences", [...$body, 'kind' => 'block'])
            ->assertForbidden();

        $this->withHeaders(asUser(memberOf($salon['activity']->organization, OrganizationRoleEnum::ACTIVITY_MANAGER, $salon['activity']->id)))
            ->postJson("/api/organizations/{$lea->organization_id}/resources/{$lea->id}/absences", [...$body, 'starts_at' => '2026-12-28T00:00:00+01:00', 'ends_at' => '2026-12-29T00:00:00+01:00'])
            ->assertCreated();

        $this->postJson("/api/organizations/{$lea->organization_id}/resources/{$table->id}/absences", [...$body, 'kind' => 'block'])
            ->assertForbidden();

        $this->withHeaders(asUser($lea->organization->owner))
            ->postJson("/api/organizations/{$lea->organization_id}/resources/{$table->id}/absences", [...$body, 'kind' => 'block'])
            ->assertCreated()
            ->assertJsonPath('kind', 'block');
    });

    it('refuses an absence over an appointment or another absence', function () {
        $salon = appointmentSalon();
        $lea = $salon['resource'];
        $start = CarbonImmutable::now()->addDays(2)->setTime(10, 0);
        Booking::factory()->appointment($salon, $start->toIso8601String())->create();

        $this->withHeaders(asUser($lea->organization->owner))
            ->postJson("/api/organizations/{$lea->organization_id}/resources/{$lea->id}/absences", [
                'starts_at' => $start->subHours(2)->toIso8601String(),
                'ends_at' => $start->addMinutes(30)->toIso8601String(),
            ])
            ->assertConflict()
            ->assertJson(['message' => __('agenda.occupied')]);

        $this->postJson("/api/organizations/{$lea->organization_id}/resources/{$lea->id}/absences", [
            'starts_at' => $start->subHours(2)->toIso8601String(),
            'ends_at' => $start->toIso8601String(),
        ])->assertCreated();
    });

    it('removes an absence, never an appointment', function () {
        $salon = appointmentSalon();
        $lea = $salon['resource'];
        $absence = ResourceBooking::factory()->for($lea, 'resource')->create();
        $appointment = Booking::factory()->appointment($salon, now()->addDays(2)->toIso8601String())->create()->items()->sole()->resourceBooking;
        $this->withHeaders(asUser($lea->organization->owner));

        $this->deleteJson("/api/organizations/{$lea->organization_id}/resources/{$lea->id}/absences/{$appointment->id}")
            ->assertNotFound();

        $this->deleteJson("/api/organizations/{$lea->organization_id}/resources/{$lea->id}/absences/{$absence->id}")
            ->assertNoContent();

        expect(ResourceBooking::query()->pluck('id')->all())->toBe([$appointment->id]);
    });
});
