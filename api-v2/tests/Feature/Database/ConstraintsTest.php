<?php

declare(strict_types=1);

use App\Enums\OrganizationRoleEnum;
use App\Enums\ServiceOfferEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Models\Activity;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\PricingPeriod;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserAddress;
use Database\Seeders\Reference\SubscriptionPlanSeeder;

// Garde-fous de la base appliqués par SQLite comme par PostgreSQL. Ceux que seule PostgreSQL applique sont dans
// PostgresConstraintsTest.

describe('unique among the rows that count', function () {
    it('keeps a SIREN to one organization, closed ones aside', function () {
        Organization::factory()->create(['siren' => '123456789'])->delete();
        Organization::factory()->create(['siren' => '123456789']);

        expectViolation('organizations_siren_unique', fn () => Organization::factory()->create(['siren' => '123456789']));
    });

    it('keeps an email to one account, deleted ones aside', function () {
        User::factory()->create(['email' => 'lea@example.com'])->delete();
        User::factory()->create(['email' => 'lea@example.com']);

        expectViolation('users_email_unique', fn () => User::factory()->create(['email' => 'lea@example.com']));
    });

    it('keeps one current subscription per organization', function () {
        $this->seed(SubscriptionPlanSeeder::class);
        $organization = Organization::factory()->create();
        Subscription::factory()->for($organization)->create(['status' => SubscriptionStatusEnum::CANCELED]);
        Subscription::factory()->for($organization)->create(['status' => SubscriptionStatusEnum::ACTIVE]);

        expectViolation('subscriptions_organization_current_unique', fn () => Subscription::factory()->for($organization)->create(['status' => SubscriptionStatusEnum::PAST_DUE]));
    });

    it('keeps one base period per organization', function () {
        // Créée avec l'entreprise.
        $organization = Organization::factory()->create();

        expect($organization->pricingPeriods()->whereNull('start_date')->count())->toBe(1);
        expectViolation('pricing_periods_base_unique', fn () => PricingPeriod::factory()->for($organization)->create(['start_date' => null, 'end_date' => null]));
    });

    it('keeps one default address per client', function () {
        $client = User::factory()->create();
        UserAddress::factory()->for($client)->create(['is_default' => true]);
        UserAddress::factory()->for($client)->create(['is_default' => false]);

        expectViolation('user_addresses_default_unique', fn () => UserAddress::factory()->for($client)->create(['is_default' => true]));
    });
});

describe('what an activity uses belongs to its organization', function () {
    it('refuses to sell the service of another organization', function () {
        $activity = Activity::factory()->create();
        $foreign = Service::factory()->create();

        expectViolation('activity_services_service_foreign', fn () => $activity->services()->attach($foreign->id, [
            'organization_id' => $activity->organization_id,
            'offered_as' => ServiceOfferEnum::STANDALONE,
            'adjustment_percent' => '0',
            'is_included' => false,
            'is_active' => true,
        ]));
    });

    it('refuses a role on the activity of another organization', function () {
        $member = OrganizationMember::factory()->create();
        $foreign = Activity::factory()->create();

        expectViolation('organization_member_roles_activity_foreign', fn () => $member->roles()->create([
            'organization_id' => $member->organization_id,
            'role' => OrganizationRoleEnum::EMPLOYEE,
            'activity_id' => $foreign->id,
        ]));
    });
});
