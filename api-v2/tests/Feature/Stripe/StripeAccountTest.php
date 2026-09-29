<?php

declare(strict_types=1);

use App\Enums\OrganizationRoleEnum;
use App\Models\Organization;

it('creates the Connect account of the company on first use and opens a session', function () {
    $organization = Organization::factory()->create();
    $this->stripe()
        ->fake('post', '/v1/accounts', ['object' => 'account', 'id' => 'acct_org'])
        ->fake('post', '/v1/account_sessions', ['object' => 'account_session', 'client_secret' => 'acs_secret']);

    $this->withHeaders(asUser($organization->owner))
        ->postJson("/api/organizations/{$organization->id}/stripe/account-session")
        ->assertOk()
        ->assertJsonPath('client_secret', 'acs_secret');

    expect($organization->fresh()->stripe_account_id)->toBe('acct_org');
    $this->stripe()->assertSent('post', '/v1/accounts', fn (array $params): bool => $params['metadata']['organization_id'] === $organization->id);
});

it('reuses the existing Connect account', function () {
    $organization = Organization::factory()->create();
    $organization->forceFill(['stripe_account_id' => 'acct_org'])->save();
    $this->stripe()->fake('post', '/v1/account_sessions', ['object' => 'account_session', 'client_secret' => 'acs_secret']);

    $this->withHeaders(asUser($organization->owner))
        ->postJson("/api/organizations/{$organization->id}/stripe/account-session")
        ->assertOk();

    $this->stripe()->assertNotSent('post', '/v1/accounts');
});

it('refreshes the account status from Stripe', function () {
    $organization = Organization::factory()->create();
    $organization->forceFill(['stripe_account_id' => 'acct_org'])->save();
    $this->stripe()->fake('get', '/v1/accounts/acct_org', [
        'object' => 'account',
        'id' => 'acct_org',
        'charges_enabled' => true,
        'payouts_enabled' => true,
        'details_submitted' => true,
    ]);

    $this->withHeaders(asUser($organization->owner))
        ->getJson("/api/organizations/{$organization->id}/stripe/status")
        ->assertOk()
        ->assertJsonPath('stripe_charges_enabled', true)
        ->assertJsonPath('stripe_onboarding_completed', true);
});

it('forbids a member who cannot manage billing', function () {
    $organization = Organization::factory()->create();

    $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::ACCOUNTANT)))
        ->postJson("/api/organizations/{$organization->id}/stripe/account-session")
        ->assertForbidden();
});
