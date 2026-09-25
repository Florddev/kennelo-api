<?php

declare(strict_types=1);

use App\Enums\OrganizationRoleEnum;
use App\Models\ActivityPeriodSetting;
use App\Models\Organization;
use App\Models\PricingPeriod;
use App\Models\User;

it('creates the base period with the company', function () {
    $owner = User::factory()->create();

    $organization = $this->withHeaders(asUser($owner))
        ->postJson('/api/organizations', ['legal_name' => 'Les Pattes', 'legal_form' => 'individual'])
        ->assertCreated()
        ->json('data.id');

    $this->getJson("/api/organizations/{$organization}/pricing-periods")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.is_base', true)
        ->assertJsonPath('data.0.name', __('booking.base_period'))
        ->assertJsonPath('data.0.start_date', null);
});

it('lists the base period first, then the others in their creation order', function () {
    $organization = Organization::factory()->create();
    $winter = PricingPeriod::factory()->for($organization)->create(['name' => 'Hiver', 'created_at' => now()->subDay()]);
    $summer = PricingPeriod::factory()->for($organization)->create(['name' => 'Été']);

    $this->withHeaders(asUser($organization->owner))
        ->getJson("/api/organizations/{$organization->id}/pricing-periods")
        ->assertJsonPath('data.*.name', [__('booking.base_period'), 'Hiver', 'Été'])
        ->assertJsonPath('data.1.id', $winter->id)
        ->assertJsonPath('data.2.id', $summer->id);
});

describe('store', function () {
    it('creates a recurring period across the 31st of December', function () {
        $organization = Organization::factory()->create();

        $this->withHeaders(asUser($organization->owner))
            ->postJson("/api/organizations/{$organization->id}/pricing-periods", [
                'name' => 'Fêtes',
                'start_date' => '2026-12-20',
                'end_date' => '2026-01-05',
                'is_recurring' => true,
                'color' => '#E4572E',
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_recurring', true)
            ->assertJsonPath('data.end_date', '2026-01-05');
    });

    it('refuses a dated period that ends before it starts', function () {
        $organization = Organization::factory()->create();

        $this->withHeaders(asUser($organization->owner))
            ->postJson("/api/organizations/{$organization->id}/pricing-periods", [
                'name' => 'Été',
                'start_date' => '2026-08-31',
                'end_date' => '2026-07-01',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
    });

    it('stops at the period quota of the plan, the base period aside', function () {
        $organization = Organization::factory()->create();
        PricingPeriod::factory()->for($organization)->count(3)->create();

        $this->withHeaders(asUser($organization->owner))
            ->postJson("/api/organizations/{$organization->id}/pricing-periods", [
                'name' => 'Noël',
                'start_date' => '2026-12-20',
                'end_date' => '2026-12-31',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan' => __('plans.limit_reached.periods', ['limit' => 3])]);
    });

    it('is reserved to the members who manage the catalog', function () {
        $organization = Organization::factory()->create();
        $payload = ['name' => 'Été', 'start_date' => '2026-07-01', 'end_date' => '2026-08-31'];

        $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::ACCOUNTANT)))
            ->postJson("/api/organizations/{$organization->id}/pricing-periods", $payload)
            ->assertForbidden();
    });

    it('hides the company from outsiders', function () {
        $organization = Organization::factory()->create();

        $this->withHeaders(asUser(User::factory()->create()))
            ->getJson("/api/organizations/{$organization->id}/pricing-periods")
            ->assertNotFound();
    });
});

describe('base period', function () {
    it('only changes its name and color', function () {
        $organization = Organization::factory()->create();
        $base = $organization->pricingPeriods()->base()->sole();

        $this->withHeaders(asUser($organization->owner))
            ->patchJson("/api/organizations/{$organization->id}/pricing-periods/{$base->id}", ['start_date' => '2026-07-01'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date']);

        $this->withHeaders(asUser($organization->owner))
            ->patchJson("/api/organizations/{$organization->id}/pricing-periods/{$base->id}", ['name' => 'Toute l\'année'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Toute l\'année');
    });

    it('cannot be deleted', function () {
        $organization = Organization::factory()->create();
        $base = $organization->pricingPeriods()->base()->sole();

        $this->withHeaders(asUser($organization->owner))
            ->deleteJson("/api/organizations/{$organization->id}/pricing-periods/{$base->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['period' => __('booking.base_period_locked')]);
    });
});

it('deletes a period with the settings of the activities for it', function () {
    $unitType = dogBoarding();
    $organization = $unitType->activity->organization;
    $summer = PricingPeriod::factory()->for($organization)->create();

    $this->withHeaders(asUser($organization->owner))
        ->putJson("/api/activities/{$unitType->activity_id}/pricing-periods/{$summer->id}/prices", [
            'prices' => [['unit_type_id' => $unitType->id, 'price' => '40.00']],
        ])
        ->assertOk();

    $this->deleteJson("/api/organizations/{$organization->id}/pricing-periods/{$summer->id}")
        ->assertNoContent();

    expect(ActivityPeriodSetting::query()->where('pricing_period_id', $summer->id)->exists())->toBeFalse();
});

it('does not reach the period of another company', function () {
    $organization = Organization::factory()->create();
    $other = PricingPeriod::factory()->create();

    $this->withHeaders(asUser($organization->owner))
        ->deleteJson("/api/organizations/{$organization->id}/pricing-periods/{$other->id}")
        ->assertNotFound();
});
