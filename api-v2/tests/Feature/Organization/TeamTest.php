<?php

declare(strict_types=1);

use App\Enums\OrganizationMemberStatusEnum;
use App\Enums\OrganizationRoleEnum;
use App\Enums\PlanEnum;
use App\Models\Activity;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\AppNotification;
use Database\Seeders\Reference\SubscriptionPlanSeeder;
use Illuminate\Support\Facades\Notification;

/**
 * Entreprise abonnée à l'offre Pro : son quota de membres n'est pas limité.
 */
function unlimitedOrganization(): Organization
{
    test()->seed(SubscriptionPlanSeeder::class);
    $organization = Organization::factory()->create();
    Subscription::factory()->for($organization)->onPlan(PlanEnum::PRO)->create();

    return $organization;
}

describe('index', function () {
    it('lists active members and pending invitations, without declined ones', function () {
        $organization = Organization::factory()->create();
        OrganizationMember::factory()->for($organization)->withRole(OrganizationRoleEnum::ACCOUNTANT)->create();
        OrganizationMember::factory()->for($organization)->pending()->create();
        OrganizationMember::factory()->for($organization)->declined()->create();

        $this->withHeaders(asUser($organization->owner))
            ->getJson("/api/organizations/{$organization->id}/members")
            ->assertOk()
            ->assertJsonCount(3)
            ->assertJsonPath('0.is_owner', true)
            ->assertJsonPath('1.roles.0.role', 'accountant');
    });

    it('returns 404 to someone outside the company', function () {
        $organization = Organization::factory()->create();

        $this->withHeaders(asUser(User::factory()->create()))
            ->getJson("/api/organizations/{$organization->id}/members")
            ->assertNotFound();
    });
});

describe('invite', function () {
    it('invites an existing account and notifies the invitee', function () {
        Notification::fake();
        $organization = unlimitedOrganization();
        $invitee = User::factory()->create(['email' => 'lea@example.com']);

        $this->withHeaders(asUser($organization->owner))
            ->postJson("/api/organizations/{$organization->id}/members", ['email' => 'lea@example.com'])
            ->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('user.id', $invitee->id);

        Notification::assertSentTo($invitee, AppNotification::class);
    });

    it('invites again someone who declined', function () {
        $organization = unlimitedOrganization();
        $declined = OrganizationMember::factory()->for($organization)->declined()->create();

        $this->withHeaders(asUser($organization->owner))
            ->postJson("/api/organizations/{$organization->id}/members", ['email' => $declined->user->email])
            ->assertOk()
            ->assertJsonPath('id', $declined->id)
            ->assertJsonPath('status', 'pending');
    });

    it('rejects an email without a Kennelo account', function () {
        $organization = unlimitedOrganization();

        $this->withHeaders(asUser($organization->owner))
            ->postJson("/api/organizations/{$organization->id}/members", ['email' => 'nobody@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => __('team.no_account')]);
    });

    it('rejects someone who is already a member', function () {
        $organization = unlimitedOrganization();
        $member = OrganizationMember::factory()->for($organization)->create();

        $this->withHeaders(asUser($organization->owner))
            ->postJson("/api/organizations/{$organization->id}/members", ['email' => $member->user->email])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => __('team.already_member')]);
    });

    it('rejects someone who already has a pending invitation', function () {
        $organization = unlimitedOrganization();
        $invitee = OrganizationMember::factory()->for($organization)->pending()->create();

        $this->withHeaders(asUser($organization->owner))
            ->postJson("/api/organizations/{$organization->id}/members", ['email' => $invitee->user->email])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => __('team.already_invited')]);
    });

    it('stops at the member quota of the free plan', function () {
        $organization = Organization::factory()->create();
        $invitee = User::factory()->create();

        $this->withHeaders(asUser($organization->owner))
            ->postJson("/api/organizations/{$organization->id}/members", ['email' => $invitee->email])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan' => __('plans.limit_reached.members', ['limit' => 1])]);
    });

    it('counts pending invitations in the member quota', function () {
        test()->seed(SubscriptionPlanSeeder::class);
        $organization = Organization::factory()->create();
        Subscription::factory()->for($organization)->onPlan(PlanEnum::STARTER)->create();
        OrganizationMember::factory()->for($organization)->count(3)->create();
        OrganizationMember::factory()->for($organization)->pending()->create();

        $this->withHeaders(asUser($organization->owner))
            ->postJson("/api/organizations/{$organization->id}/members", ['email' => User::factory()->create()->email])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['plan']);
    });

    it('forbids a member who cannot manage the team', function () {
        $organization = unlimitedOrganization();

        $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::ACCOUNTANT)))
            ->postJson("/api/organizations/{$organization->id}/members", ['email' => User::factory()->create()->email])
            ->assertForbidden();
    });
});

describe('invitations', function () {
    it('lists the pending invitations of the connected user, without closed companies', function () {
        $user = User::factory()->create();
        $invitation = OrganizationMember::factory()->for($user)->pending()->create();
        OrganizationMember::factory()->for($user)->create();
        OrganizationMember::factory()->for($user)->pending()->create()->organization->delete();

        $this->withHeaders(asUser($user))
            ->getJson('/api/user/invitations')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $invitation->id)
            ->assertJsonPath('0.organization.legal_name', $invitation->organization->legal_name);
    });

    it('accepts an invitation, which opens the management area and notifies the owner', function () {
        Notification::fake();
        $invitation = OrganizationMember::factory()->pending()->create();

        $this->withHeaders(asUser($invitation->user))
            ->postJson("/api/user/invitations/{$invitation->id}/accept")
            ->assertOk()
            ->assertJsonPath('status', 'active');

        expect($invitation->user->canAccessManagement())->toBeTrue();
        Notification::assertSentTo($invitation->organization->owner, AppNotification::class);
    });

    it('declines an invitation', function () {
        $invitation = OrganizationMember::factory()->pending()->create();

        $this->withHeaders(asUser($invitation->user))
            ->postJson("/api/user/invitations/{$invitation->id}/decline")
            ->assertOk()
            ->assertJsonPath('status', 'declined');

        expect($invitation->user->canAccessManagement())->toBeFalse();
    });

    it('rejects an answer to an invitation already answered', function () {
        $membership = OrganizationMember::factory()->create();

        $this->withHeaders(asUser($membership->user))
            ->postJson("/api/user/invitations/{$membership->id}/decline")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['invitation' => __('team.invitation_not_pending')]);

        expect($membership->fresh()->status)->toBe(OrganizationMemberStatusEnum::ACTIVE);
    });

    it('returns 404 to someone other than the invitee', function () {
        $invitation = OrganizationMember::factory()->pending()->create();

        $this->withHeaders(asUser(User::factory()->create()))
            ->postJson("/api/user/invitations/{$invitation->id}/accept")
            ->assertNotFound();

        expect($invitation->fresh()->isPending())->toBeTrue();
    });

    it('returns 404 for the invitation of a closed company', function () {
        $invitation = OrganizationMember::factory()->pending()->create();
        $invitation->organization->delete();

        $this->withHeaders(asUser($invitation->user))
            ->postJson("/api/user/invitations/{$invitation->id}/accept")
            ->assertNotFound();
    });
});

describe('roles', function () {
    it('replaces the roles of a member', function () {
        $organization = Organization::factory()->create();
        $activityId = Activity::factory()->for($organization)->create()->id;
        $member = OrganizationMember::factory()->for($organization)->withRole(OrganizationRoleEnum::MANAGER)->create();

        $this->withHeaders(asUser($organization->owner))
            ->putJson("/api/organizations/{$organization->id}/members/{$member->id}/roles", ['roles' => [
                ['role' => 'accountant'],
                ['role' => 'employee', 'activity_id' => $activityId],
            ]])
            ->assertOk()
            ->assertJsonCount(2, 'roles');

        expect($member->roles()->pluck('role')->all())
            ->toEqualCanonicalizing([OrganizationRoleEnum::ACCOUNTANT, OrganizationRoleEnum::EMPLOYEE]);
    });

    it('removes every role with an empty list, while keeping the member', function () {
        $organization = Organization::factory()->create();
        $member = OrganizationMember::factory()->for($organization)->withRole(OrganizationRoleEnum::MANAGER)->create();

        $this->withHeaders(asUser($organization->owner))
            ->putJson("/api/organizations/{$organization->id}/members/{$member->id}/roles", ['roles' => []])
            ->assertOk()
            ->assertJsonCount(0, 'roles');

        expect($member->fresh())->not->toBeNull();
    });

    it('requires an activity for an activity role', function () {
        $organization = Organization::factory()->create();
        $member = OrganizationMember::factory()->for($organization)->create();

        $this->withHeaders(asUser($organization->owner))
            ->putJson("/api/organizations/{$organization->id}/members/{$member->id}/roles", ['roles' => [['role' => 'employee']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['roles.0.activity_id' => __('team.role_requires_activity')]);
    });

    it('refuses an activity for a company-wide role', function () {
        $organization = Organization::factory()->create();
        $member = OrganizationMember::factory()->for($organization)->create();

        $this->withHeaders(asUser($organization->owner))
            ->putJson("/api/organizations/{$organization->id}/members/{$member->id}/roles", ['roles' => [
                ['role' => 'manager', 'activity_id' => Activity::factory()->for($organization)->create()->id],
            ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['roles.0.activity_id' => __('team.role_forbids_activity')]);
    });

    it('refuses an activity of another company', function () {
        $organization = Organization::factory()->create();
        $member = OrganizationMember::factory()->for($organization)->create();

        $this->withHeaders(asUser($organization->owner))
            ->putJson("/api/organizations/{$organization->id}/members/{$member->id}/roles", ['roles' => [
                ['role' => 'employee', 'activity_id' => Activity::factory()->create()->id],
            ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['roles.0.activity_id']);
    });

    it('refuses two roles on the same scope', function () {
        $organization = Organization::factory()->create();
        $member = OrganizationMember::factory()->for($organization)->create();

        $this->withHeaders(asUser($organization->owner))
            ->putJson("/api/organizations/{$organization->id}/members/{$member->id}/roles", ['roles' => [
                ['role' => 'manager'],
                ['role' => 'accountant'],
            ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['roles' => __('team.duplicate_scope')]);
    });

    it('refuses to give a role to the owner', function () {
        $organization = Organization::factory()->create();
        $ownerMembership = $organization->members()->firstOrFail();

        $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::MANAGER)))
            ->putJson("/api/organizations/{$organization->id}/members/{$ownerMembership->id}/roles", ['roles' => [['role' => 'accountant']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['roles' => __('team.owner_has_no_roles')]);
    });

    it('returns 404 for a member of another company', function () {
        $organization = Organization::factory()->create();
        $outsider = OrganizationMember::factory()->create();

        $this->withHeaders(asUser($organization->owner))
            ->putJson("/api/organizations/{$organization->id}/members/{$outsider->id}/roles", ['roles' => []])
            ->assertNotFound();
    });
});

describe('remove', function () {
    it('lets a manager remove a member, with their roles', function () {
        $organization = Organization::factory()->create();
        $member = OrganizationMember::factory()->for($organization)->withRole(OrganizationRoleEnum::ACCOUNTANT)->create();

        $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::MANAGER)))
            ->deleteJson("/api/organizations/{$organization->id}/members/{$member->id}")
            ->assertNoContent();

        expect($member->fresh())->toBeNull()
            ->and($member->user->canAccessManagement())->toBeFalse();
    });

    it('lets a member leave the company', function () {
        $organization = Organization::factory()->create();
        $member = OrganizationMember::factory()->for($organization)->create();

        $this->withHeaders(asUser($member->user))
            ->deleteJson("/api/organizations/{$organization->id}/members/{$member->id}")
            ->assertNoContent();

        expect($member->fresh())->toBeNull();
    });

    it('refuses to remove the owner', function () {
        $organization = Organization::factory()->create();
        $ownerMembership = $organization->members()->firstOrFail();

        $this->withHeaders(asUser($organization->owner))
            ->deleteJson("/api/organizations/{$organization->id}/members/{$ownerMembership->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['member' => __('team.owner_cannot_leave')]);
    });

    it('forbids a member from removing a colleague without managing the team', function () {
        $organization = Organization::factory()->create();
        $colleague = OrganizationMember::factory()->for($organization)->create();

        $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::ACCOUNTANT)))
            ->deleteJson("/api/organizations/{$organization->id}/members/{$colleague->id}")
            ->assertForbidden();
    });

    it('returns 404 to someone outside the company', function () {
        $organization = Organization::factory()->create();
        $member = OrganizationMember::factory()->for($organization)->create();

        $this->withHeaders(asUser(User::factory()->create()))
            ->deleteJson("/api/organizations/{$organization->id}/members/{$member->id}")
            ->assertNotFound();
    });
});
