<?php

declare(strict_types=1);

use App\Enums\OrganizationRoleEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\Reference\SubscriptionPlanSeeder;

describe('store', function () {
    it('creates a company and makes the creator its active owner', function () {
        $user = User::factory()->create();

        $response = $this->withHeaders(asUser($user))
            ->postJson('/api/organizations', [
                'legal_name' => 'Pension des Lilas',
                'legal_form' => 'company',
                'siren' => '123456789',
                'siret' => '12345678900012',
                'address' => ['line1' => '3 rue des Lilas', 'postal_code' => '69003', 'city' => 'Lyon', 'country' => 'FR'],
            ])
            ->assertCreated()
            ->assertJsonPath('legal_name', 'Pension des Lilas')
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('plan', 'free')
            ->assertJsonPath('is_owner', true)
            ->assertJsonPath('address.department', '69');

        $organization = Organization::findOrFail($response->json('id'));

        expect($organization->owner_id)->toBe($user->id)
            ->and($organization->members()->active()->whereBelongsTo($user)->exists())->toBeTrue()
            ->and($user->canAccessManagement())->toBeTrue();
    });

    it('creates an individual without company identifiers', function () {
        $this->withHeaders(asUser(User::factory()->create()))
            ->postJson('/api/organizations', [
                'legal_name' => 'Marie Dupont',
                'legal_form' => 'individual',
                'siren' => '123456789',
                'vat_regime' => 'standard',
            ])
            ->assertCreated()
            ->assertJsonPath('siren', null)
            ->assertJsonPath('vat_regime', 'franchise');
    });

    it('requires a SIREN for a company', function () {
        $this->withHeaders(asUser(User::factory()->create()))
            ->postJson('/api/organizations', ['legal_name' => 'Pension des Lilas', 'legal_form' => 'company'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['siren']);
    });

    it('rejects a SIREN already used by another open company', function () {
        Organization::factory()->create(['siren' => '123456789']);

        $this->withHeaders(asUser(User::factory()->create()))
            ->postJson('/api/organizations', ['legal_name' => 'Pension des Lilas', 'legal_form' => 'company', 'siren' => '123456789'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['siren']);
    });

    it('accepts the SIREN of a closed company', function () {
        Organization::factory()->create(['siren' => '123456789'])->delete();

        $this->withHeaders(asUser(User::factory()->create()))
            ->postJson('/api/organizations', ['legal_name' => 'Pension des Lilas', 'legal_form' => 'company', 'siren' => '123456789'])
            ->assertCreated();
    });

    it('rejects a SIRET that does not belong to the SIREN', function () {
        $this->withHeaders(asUser(User::factory()->create()))
            ->postJson('/api/organizations', [
                'legal_name' => 'Pension des Lilas',
                'legal_form' => 'company',
                'siren' => '123456789',
                'siret' => '98765432100012',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['siret' => __('organization.siret_mismatch')]);
    });

    it('requires authentication', function () {
        $this->postJson('/api/organizations', [])->assertUnauthorized();
    });
});

describe('index', function () {
    it('lists only the open companies where the user is an active member', function () {
        $user = User::factory()->create();
        $owned = Organization::factory()->for($user, 'owner')->create();
        $joined = Organization::factory()->create();
        OrganizationMember::factory()->for($joined)->for($user)->create();
        OrganizationMember::factory()->for(Organization::factory())->for($user)->pending()->create();
        Organization::factory()->for($user, 'owner')->create()->delete();
        Organization::factory()->create();

        $this->withHeaders(asUser($user))
            ->getJson('/api/organizations')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('*.id', fn (array $ids): bool => collect($ids)->sort()->values()->all() === collect([$owned->id, $joined->id])->sort()->values()->all());
    });
});

describe('show', function () {
    it('returns the company with the permissions of the connected member', function () {
        $organization = Organization::factory()->create();
        $accountant = memberOf($organization, OrganizationRoleEnum::ACCOUNTANT);

        $this->withHeaders(asUser($accountant))
            ->getJson("/api/organizations/{$organization->id}")
            ->assertOk()
            ->assertJsonPath('is_owner', false)
            ->assertJsonPath('permissions', ['finance.view']);
    });

    it('returns 404 to someone outside the company', function () {
        $organization = Organization::factory()->create();

        $this->withHeaders(asUser(User::factory()->create()))
            ->getJson("/api/organizations/{$organization->id}")
            ->assertNotFound()
            ->assertJsonPath('message', __('errors.not_found'));
    });

    it('returns 404 for a malformed identifier', function () {
        $this->withHeaders(asUser(User::factory()->create()))
            ->getJson('/api/organizations/not-a-uuid')
            ->assertNotFound();
    });
});

describe('update', function () {
    it('lets a manager update the company', function () {
        $organization = Organization::factory()->create();

        $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::MANAGER)))
            ->patchJson("/api/organizations/{$organization->id}", ['vat_number' => 'FR12123456789'])
            ->assertOk()
            ->assertJsonPath('vat_number', 'FR12123456789');
    });

    it('forbids a member without the permission', function () {
        $organization = Organization::factory()->create();

        $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::ACCOUNTANT)))
            ->patchJson("/api/organizations/{$organization->id}", ['vat_number' => 'FR12123456789'])
            ->assertForbidden();
    });

    it('sends a verified company back to review when its identity changes', function () {
        $organization = Organization::factory()->verified()->create();

        $this->withHeaders(asUser($organization->owner))
            ->patchJson("/api/organizations/{$organization->id}", ['legal_name' => 'Nouvelle raison sociale'])
            ->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('verified_at', null);
    });

    it('keeps a verified company verified when only its address changes', function () {
        $organization = Organization::factory()->verified()->create();

        $this->withHeaders(asUser($organization->owner))
            ->patchJson("/api/organizations/{$organization->id}", [
                'address' => ['line1' => '3 rue des Lilas', 'postal_code' => '69003', 'city' => 'Lyon', 'country' => 'FR'],
            ])
            ->assertOk()
            ->assertJsonPath('status', 'verified');
    });

    it('clears the company identifiers when it becomes an individual', function () {
        $organization = Organization::factory()->create(['vat_number' => 'FR12123456789']);

        $this->withHeaders(asUser($organization->owner))
            ->patchJson("/api/organizations/{$organization->id}", ['legal_form' => 'individual'])
            ->assertOk()
            ->assertJsonPath('siren', null)
            ->assertJsonPath('siret', null)
            ->assertJsonPath('vat_number', null)
            ->assertJsonPath('vat_regime', 'franchise');
    });

    it('requires a SIREN when an individual becomes a company', function () {
        $organization = Organization::factory()->individual()->create();

        $this->withHeaders(asUser($organization->owner))
            ->patchJson("/api/organizations/{$organization->id}", ['legal_form' => 'company'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['siren']);
    });
});

describe('destroy', function () {
    it('lets the owner close the company, which removes management access from its members', function () {
        $organization = Organization::factory()->create();
        $manager = memberOf($organization, OrganizationRoleEnum::MANAGER);

        $this->withHeaders(asUser($organization->owner))
            ->deleteJson("/api/organizations/{$organization->id}")
            ->assertNoContent();

        expect($organization->fresh()->trashed())->toBeTrue()
            ->and($manager->canAccessManagement())->toBeFalse();
    });

    it('forbids a manager from closing the company', function () {
        $organization = Organization::factory()->create();

        $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::MANAGER)))
            ->deleteJson("/api/organizations/{$organization->id}")
            ->assertForbidden();
    });

    it('refuses to close a company whose subscription is still running', function () {
        $this->seed(SubscriptionPlanSeeder::class);
        $organization = Organization::factory()->create();
        Subscription::factory()->for($organization)->create();

        $this->withHeaders(asUser($organization->owner))
            ->deleteJson("/api/organizations/{$organization->id}")
            ->assertUnprocessable()
            ->assertJsonPath('message', __('organization.subscription_still_active'));

        expect($organization->fresh()->trashed())->toBeFalse();
    });

    it('closes a company whose subscription is ending', function () {
        $this->seed(SubscriptionPlanSeeder::class);
        $organization = Organization::factory()->create();
        Subscription::factory()->for($organization)->canceling()->create();

        $this->withHeaders(asUser($organization->owner))
            ->deleteJson("/api/organizations/{$organization->id}")
            ->assertNoContent();
    });

    it('closes a company whose subscription is unpaid', function () {
        $this->seed(SubscriptionPlanSeeder::class);
        $organization = Organization::factory()->create();
        Subscription::factory()->for($organization)->status(SubscriptionStatusEnum::UNPAID)->create();

        $this->withHeaders(asUser($organization->owner))
            ->deleteJson("/api/organizations/{$organization->id}")
            ->assertNoContent();
    });
});

describe('transfer ownership', function () {
    it('makes an active member the owner and keeps the former owner as manager', function () {
        $organization = Organization::factory()->create();
        $formerOwner = $organization->owner;
        $member = OrganizationMember::factory()->for($organization)->withRole(OrganizationRoleEnum::ACCOUNTANT)->create();

        $this->withHeaders(asUser($formerOwner))
            ->putJson("/api/organizations/{$organization->id}/owner", ['member_id' => $member->id])
            ->assertOk()
            ->assertJsonPath('is_owner', false);

        $formerMembership = $organization->members()->whereBelongsTo($formerOwner)->firstOrFail();

        expect($organization->fresh()->owner_id)->toBe($member->user_id)
            ->and($member->roles()->exists())->toBeFalse()
            ->and($formerMembership->roles()->pluck('role')->all())->toBe([OrganizationRoleEnum::MANAGER]);
    });

    it('refuses a member whose invitation is pending', function () {
        $organization = Organization::factory()->create();
        $invitee = OrganizationMember::factory()->for($organization)->pending()->create();

        $this->withHeaders(asUser($organization->owner))
            ->putJson("/api/organizations/{$organization->id}/owner", ['member_id' => $invitee->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['member_id' => __('organization.transfer_requires_active_member')]);
    });

    it('refuses a member of another company', function () {
        $organization = Organization::factory()->create();
        $outsider = OrganizationMember::factory()->create();

        $this->withHeaders(asUser($organization->owner))
            ->putJson("/api/organizations/{$organization->id}/owner", ['member_id' => $outsider->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['member_id']);
    });

    it('forbids a manager from transferring the company', function () {
        $organization = Organization::factory()->create();
        $member = OrganizationMember::factory()->for($organization)->create();

        $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::MANAGER)))
            ->putJson("/api/organizations/{$organization->id}/owner", ['member_id' => $member->id])
            ->assertForbidden();
    });
});
