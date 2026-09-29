<?php

declare(strict_types=1);

use App\Enums\OrganizationRoleEnum;
use App\Models\Activity;
use App\Models\Organization;

/**
 * Activité approuvée d'une entreprise vérifiée qui peut encaisser, mais qui n'a pas encore donné son mandat.
 */
function activityWithoutMandate(): Activity
{
    return Activity::factory()->approved()->state([
        'organization_id' => Organization::factory()->verified()->withStripe(),
    ])->create();
}

it('lets the owner give the mandate, which makes the activities bookable', function () {
    $activity = activityWithoutMandate();
    $organization = $activity->organization;

    expect(Activity::bookable()->whereKey($activity->id)->exists())->toBeFalse();

    $this->withHeaders(asUser($organization->owner))
        ->postJson("/api/organizations/{$organization->id}/billing-mandate", ['accepted' => true])
        ->assertOk()
        ->assertJsonPath('billing_mandate_accepted_at', fn (?string $date): bool => $date !== null);

    expect($organization->fresh()->billing_mandate_accepted_by)->toBe($organization->owner_id)
        ->and(Activity::bookable()->whereKey($activity->id)->exists())->toBeTrue();
});

it('keeps the date of the first acceptance', function () {
    $organization = Organization::factory()->withBillingMandate()->create();
    $acceptedAt = $organization->billing_mandate_accepted_at?->toISOString();
    $this->travel(2)->days();

    $this->withHeaders(asUser($organization->owner))
        ->postJson("/api/organizations/{$organization->id}/billing-mandate", ['accepted' => true])
        ->assertOk()
        ->assertJsonPath('billing_mandate_accepted_at', $acceptedAt);
});

it('requires an explicit acceptance', function () {
    $organization = Organization::factory()->create();

    $this->withHeaders(asUser($organization->owner))
        ->postJson("/api/organizations/{$organization->id}/billing-mandate", ['accepted' => false])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['accepted']);

    expect($organization->fresh()->billing_mandate_accepted_at)->toBeNull();
});

it('lets a manager give the mandate, not an accountant nor an outsider', function () {
    $organization = Organization::factory()->create();

    $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::ACCOUNTANT)))
        ->postJson("/api/organizations/{$organization->id}/billing-mandate", ['accepted' => true])
        ->assertForbidden();
    $this->withHeaders(asUser(Organization::factory()->create()->owner))
        ->postJson("/api/organizations/{$organization->id}/billing-mandate", ['accepted' => true])
        ->assertNotFound();
    $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::MANAGER)))
        ->postJson("/api/organizations/{$organization->id}/billing-mandate", ['accepted' => true])
        ->assertOk();
});

it('hides from clients an activity whose company has not given the mandate', function () {
    $activity = activityWithoutMandate();

    $this->getJson("/api/activities/{$activity->id}")->assertNotFound();
});
