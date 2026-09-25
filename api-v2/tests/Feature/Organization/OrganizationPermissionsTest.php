<?php

declare(strict_types=1);

use App\Enums\OrganizationPermissionEnum as Permission;
use App\Enums\OrganizationRoleEnum;
use App\Models\Activity;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Services\Organization\OrganizationPermissions;

function permissionValues(array $permissions): array
{
    return array_map(fn (Permission $permission): string => $permission->value, $permissions);
}

test('each company-wide role grants exactly its permissions on the whole company', function (OrganizationRoleEnum $role, array $expected) {
    $organization = Organization::factory()->create();
    $member = memberOf($organization, $role);

    $permissions = app(OrganizationPermissions::class)->permissionsFor($member, $organization);

    expect(permissionValues($permissions))->toEqualCanonicalizing($expected);
})->with([
    'manager' => [OrganizationRoleEnum::MANAGER, Permission::values()],
    'accountant' => [OrganizationRoleEnum::ACCOUNTANT, ['finance.view']],
]);

test('each activity role grants exactly its permissions on its activity', function (OrganizationRoleEnum $role, array $expected) {
    $organization = Organization::factory()->create();
    $activityId = Activity::factory()->for($organization)->create()->id;
    $member = memberOf($organization, $role, $activityId);

    $permissions = app(OrganizationPermissions::class)->permissionsFor($member, $organization, $activityId);

    expect(permissionValues($permissions))->toEqualCanonicalizing($expected);
})->with([
    'activity manager' => [OrganizationRoleEnum::ACTIVITY_MANAGER, ['activity.manage', 'bookings.manage', 'bookings.view', 'agenda.manage', 'messages.reply']],
    'employee' => [OrganizationRoleEnum::EMPLOYEE, ['bookings.view', 'messages.reply']],
]);

it('grants every permission to the owner without any role', function () {
    $organization = Organization::factory()->create();

    $permissions = app(OrganizationPermissions::class)->permissionsFor($organization->owner, $organization);

    expect(permissionValues($permissions))->toEqualCanonicalizing(Permission::values());
});

it('limits an activity role to its own activity', function () {
    $organization = Organization::factory()->create();
    $salon = Activity::factory()->for($organization)->create()->id;
    $boarding = Activity::factory()->for($organization)->create()->id;
    $member = memberOf($organization, OrganizationRoleEnum::ACTIVITY_MANAGER, $salon);
    $permissions = app(OrganizationPermissions::class);

    expect($permissions->allows($member, Permission::BOOKINGS_MANAGE, $organization, $salon))->toBeTrue()
        ->and($permissions->allows($member, Permission::BOOKINGS_MANAGE, $organization, $boarding))->toBeFalse()
        ->and($permissions->allows($member, Permission::BOOKINGS_MANAGE, $organization))->toBeFalse();
});

it('applies a company-wide role to every activity', function () {
    $organization = Organization::factory()->create();
    $activityId = Activity::factory()->for($organization)->create()->id;
    $member = memberOf($organization, OrganizationRoleEnum::MANAGER);

    expect(app(OrganizationPermissions::class)->allows($member, Permission::BOOKINGS_MANAGE, $organization, $activityId))->toBeTrue();
});

it('grants nothing to a member whose invitation is still pending', function () {
    $organization = Organization::factory()->create();
    $invitee = OrganizationMember::factory()->for($organization)->pending()->withRole(OrganizationRoleEnum::MANAGER)->create()->user;

    expect(app(OrganizationPermissions::class)->permissionsFor($invitee, $organization))->toBe([]);
});

it('grants nothing on another company', function () {
    $organization = Organization::factory()->create();
    $manager = memberOf($organization, OrganizationRoleEnum::MANAGER);

    expect(app(OrganizationPermissions::class)->permissionsFor($manager, Organization::factory()->create()))->toBe([]);
});
