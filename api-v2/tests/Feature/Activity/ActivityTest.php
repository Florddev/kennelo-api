<?php

declare(strict_types=1);

use App\Enums\OrganizationRoleEnum;
use App\Models\Activity;
use App\Models\AnimalType;
use App\Models\Organization;
use App\Models\Profession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @return array<string, mixed>
 */
function activityPayload(Profession $profession, array $overrides = []): array
{
    return array_merge([
        'profession_id' => $profession->id,
        'name' => 'Salon des Lilas',
        'locations' => ['at_pro'],
        'animal_type_ids' => $profession->animalTypes()->pluck('animal_types.id')->all(),
        'address' => [
            'line1' => '3 rue des Lilas',
            'postal_code' => '69003',
            'city' => 'Lyon',
            'country' => 'FR',
            'latitude' => 45.76,
            'longitude' => 4.85,
        ],
    ], $overrides);
}

/**
 * Métier en rendez-vous, exercé chez le pro ou chez le client, pour les chiens.
 */
function dogProfession(): Profession
{
    return Profession::factory()->forSpecies(AnimalType::factory()->dog()->create())->create();
}

describe('store', function () {
    it('creates a pending activity with the default timezone', function () {
        $profession = dogProfession();
        $organization = Organization::factory()->create();

        $response = $this->withHeaders(asUser($organization->owner))
            ->postJson("/api/organizations/{$organization->id}/activities", activityPayload($profession))
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.timezone', 'Europe/Paris')
            ->assertJsonPath('data.locations', ['at_pro'])
            ->assertJsonPath('data.cancellation_policy', 'moderate')
            ->assertJsonPath('data.animal_types.0.code', 'dog')
            ->assertJsonPath('data.address.city', 'Lyon');

        $activity = Activity::query()->findOrFail($response->json('data.id'));

        expect($activity->organization_id)->toBe($organization->id)
            ->and($activity->serves_at_pro)->toBeTrue()
            ->and($activity->serves_at_client)->toBeFalse();
    });

    it('lets a manager create an activity', function () {
        $profession = dogProfession();
        $organization = Organization::factory()->create();

        $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::MANAGER)))
            ->postJson("/api/organizations/{$organization->id}/activities", activityPayload($profession))
            ->assertCreated();
    });

    it('reopens a paused activity only within the plan quota when paused activities are enforced', function () {
        config(['plans.downgrade.soft_disable.activities' => true]);
        $organization = Organization::factory()->create();
        Activity::factory()->for($organization)->create();
        $paused = Activity::factory()->for($organization)->create(['is_active' => false]);

        $this->withHeaders(asUser($organization->owner))
            ->patchJson("/api/activities/{$paused->id}", ['is_active' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan' => __('plans.limit_reached.activities', ['limit' => 1])]);

        $this->withHeaders(asUser($organization->owner))
            ->patchJson("/api/activities/{$paused->id}", ['name' => 'Toujours en pause'])
            ->assertOk();
    });

    it('lets a company keep its activities open beyond the quota when the option is off', function () {
        $organization = Organization::factory()->create();
        Activity::factory()->for($organization)->create();
        $paused = Activity::factory()->for($organization)->create(['is_active' => false]);

        $this->withHeaders(asUser($organization->owner))
            ->patchJson("/api/activities/{$paused->id}", ['is_active' => true])
            ->assertOk()
            ->assertJsonPath('data.is_active', true);
    });

    it('forbids the manager of another activity', function () {
        $profession = dogProfession();
        $organization = Organization::factory()->create();
        $salon = Activity::factory()->for($organization)->create();

        $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::ACTIVITY_MANAGER, $salon->id)))
            ->postJson("/api/organizations/{$organization->id}/activities", activityPayload($profession))
            ->assertForbidden();
    });

    it('returns 404 to someone outside the company', function () {
        $profession = dogProfession();
        $organization = Organization::factory()->create();

        $this->withHeaders(asUser(User::factory()->create()))
            ->postJson("/api/organizations/{$organization->id}/activities", activityPayload($profession))
            ->assertNotFound();
    });

    it('refuses a place or a species the profession does not allow', function () {
        $profession = dogProfession();
        $organization = Organization::factory()->create();
        $cat = AnimalType::factory()->cat()->create();

        $this->withHeaders(asUser($organization->owner))
            ->postJson("/api/organizations/{$organization->id}/activities", activityPayload($profession, [
                'locations' => ['remote'],
                'animal_type_ids' => [$cat->id],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'locations.0' => __('activity.location_not_allowed'),
                'animal_type_ids.0' => __('activity.animal_type_not_allowed'),
            ]);
    });

    it('requires a radius to travel to the client', function () {
        $profession = dogProfession();
        $organization = Organization::factory()->create();

        $this->withHeaders(asUser($organization->owner))
            ->postJson("/api/organizations/{$organization->id}/activities", activityPayload($profession, ['locations' => ['at_client']]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service_radius_km']);
    });

    it('requires a geolocated address', function () {
        $profession = dogProfession();
        $organization = Organization::factory()->create();
        $payload = activityPayload($profession);
        unset($payload['address']['latitude'], $payload['address']['longitude']);

        $this->withHeaders(asUser($organization->owner))
            ->postJson("/api/organizations/{$organization->id}/activities", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['address.latitude', 'address.longitude']);

        $this->withHeaders(asUser($organization->owner))
            ->postJson("/api/organizations/{$organization->id}/activities", activityPayload($profession, ['address' => null]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['address']);
    });

    it('refuses an establishment SIRET of another company', function () {
        $profession = dogProfession();
        $organization = Organization::factory()->create(['siren' => '123456789']);

        $this->withHeaders(asUser($organization->owner))
            ->postJson("/api/organizations/{$organization->id}/activities", activityPayload($profession, ['establishment_siret' => '98765432100012']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['establishment_siret' => __('activity.siret_mismatch')]);
    });

    it('refuses a closed profession', function () {
        $profession = dogProfession();
        $organization = Organization::factory()->create();
        $profession->update(['is_active' => false]);

        $this->withHeaders(asUser($organization->owner))
            ->postJson("/api/organizations/{$organization->id}/activities", activityPayload($profession))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['profession_id']);
    });

    it('stops at the activity quota of the plan', function () {
        $profession = dogProfession();
        $organization = Organization::factory()->create();
        Activity::factory()->for($organization)->create();

        $this->withHeaders(asUser($organization->owner))
            ->postJson("/api/organizations/{$organization->id}/activities", activityPayload($profession))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan' => __('plans.limit_reached.activities', ['limit' => 1])]);
    });
});

describe('show', function () {
    it('shows a bookable activity to anyone', function () {
        $activity = Activity::factory()->bookable()->create();

        $this->getJson("/api/activities/{$activity->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $activity->id)
            ->assertJsonMissingPath('data.status')
            ->assertJsonMissingPath('data.permissions');
    });

    it('hides an activity that is not bookable yet', function (Closure $makeActivity) {
        $activity = $makeActivity();

        $this->getJson("/api/activities/{$activity->id}")->assertNotFound();
        $this->withHeaders(asUser(User::factory()->create()))->getJson("/api/activities/{$activity->id}")->assertNotFound();
    })->with([
        'pending' => fn () => Activity::factory()->state(['organization_id' => Organization::factory()->verified()->withStripe()])->create(),
        'paused' => fn () => Activity::factory()->bookable()->create(['is_active' => false]),
        'company not verified' => fn () => Activity::factory()->approved()->state(['organization_id' => Organization::factory()->withStripe()])->create(),
        'company cannot charge' => fn () => Activity::factory()->approved()->state(['organization_id' => Organization::factory()->verified()])->create(),
    ]);

    it('shows a pending activity to its team with their permissions', function () {
        $organization = Organization::factory()->create();
        $activity = Activity::factory()->for($organization)->create();
        $employee = memberOf($organization, OrganizationRoleEnum::EMPLOYEE, $activity->id);

        $this->withHeaders(asUser($employee))
            ->getJson("/api/activities/{$activity->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.permissions', ['bookings.view', 'messages.reply']);
    });

    it('shows only the city of a professional who does not receive clients', function () {
        $activity = Activity::factory()->bookable()->atClient(15)->create();

        $this->getJson("/api/activities/{$activity->id}")
            ->assertOk()
            ->assertJsonMissingPath('data.address.line1')
            ->assertJsonPath('data.address.city', $activity->address->city);
    });

    it('tells a client whether the activity is in their favorites', function () {
        $activity = Activity::factory()->bookable()->create();
        $client = User::factory()->create();
        $client->favoriteActivities()->attach($activity->id, ['created_at' => now()]);

        $this->withHeaders(asUser($client))
            ->getJson("/api/activities/{$activity->id}")
            ->assertJsonPath('data.is_favorited', true);
    });
});

describe('index', function () {
    it('lists every activity of the company to its members', function () {
        $organization = Organization::factory()->create();
        Activity::factory()->for($organization)->count(2)->create();
        Activity::factory()->create();

        $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::ACCOUNTANT)))
            ->getJson("/api/organizations/{$organization->id}/activities")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });
});

describe('update', function () {
    it('lets the manager of the activity update it', function () {
        $organization = Organization::factory()->create();
        $activity = Activity::factory()->for($organization)->create();

        $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::ACTIVITY_MANAGER, $activity->id)))
            ->patchJson("/api/activities/{$activity->id}", ['name' => 'Salon rénové', 'is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.name', 'Salon rénové')
            ->assertJsonPath('data.is_active', false);
    });

    it('forbids the manager of another activity', function () {
        $organization = Organization::factory()->create();
        [$salon, $boarding] = Activity::factory()->for($organization)->count(2)->create();

        $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::ACTIVITY_MANAGER, $salon->id)))
            ->patchJson("/api/activities/{$boarding->id}", ['name' => 'Pension'])
            ->assertForbidden();
    });

    it('keeps the profession of the activity', function () {
        $activity = Activity::factory()->create();

        $this->withHeaders(asUser($activity->organization->owner))
            ->patchJson("/api/activities/{$activity->id}", ['profession_id' => Profession::factory()->create()->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['profession_id']);
    });

    it('requires a radius when the activity starts travelling to clients', function () {
        $activity = Activity::factory()->create();

        $this->withHeaders(asUser($activity->organization->owner))
            ->patchJson("/api/activities/{$activity->id}", ['locations' => ['at_pro', 'at_client']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service_radius_km']);

        $this->withHeaders(asUser($activity->organization->owner))
            ->patchJson("/api/activities/{$activity->id}", ['locations' => ['at_pro', 'at_client'], 'service_radius_km' => 25])
            ->assertOk()
            ->assertJsonPath('data.locations', ['at_pro', 'at_client'])
            ->assertJsonPath('data.service_radius_km', 25);
    });

    it('returns 404 once the company is closed', function () {
        $activity = Activity::factory()->create();
        $owner = $activity->organization->owner;
        $activity->organization->delete();

        $this->withHeaders(asUser($owner))
            ->patchJson("/api/activities/{$activity->id}", ['name' => 'Salon'])
            ->assertNotFound();
    });
});

describe('destroy', function () {
    it('lets a manager delete an activity', function () {
        $organization = Organization::factory()->create();
        $activity = Activity::factory()->for($organization)->create();

        $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::MANAGER)))
            ->deleteJson("/api/activities/{$activity->id}")
            ->assertNoContent();

        expect($activity->fresh()->trashed())->toBeTrue();
    });

    it('forbids the manager of the activity', function () {
        $organization = Organization::factory()->create();
        $activity = Activity::factory()->for($organization)->create();

        $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::ACTIVITY_MANAGER, $activity->id)))
            ->deleteJson("/api/activities/{$activity->id}")
            ->assertForbidden();
    });

    it('refuses to delete an activity with an upcoming booking', function () {
        $activity = Activity::factory()->create();
        DB::table('bookings')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => User::factory()->create()->id,
            'organization_id' => $activity->organization_id,
            'activity_id' => $activity->id,
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addWeek()->addDays(2)->toDateString(),
            'status' => 'confirmed',
            'total_price' => '120.00',
            'vat_rate' => '20.00',
            'cancellation_policy' => 'moderate',
        ]);

        $this->withHeaders(asUser($activity->organization->owner))
            ->deleteJson("/api/activities/{$activity->id}")
            ->assertUnprocessable()
            ->assertJsonPath('message', __('activity.has_active_bookings'));
    });
});
