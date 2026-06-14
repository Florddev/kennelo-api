<?php

declare(strict_types=1);

use App\Enums\ActivityPermissionEnum;
use App\Enums\CollaboratorStatusEnum;
use App\Models\Activity;
use App\Models\ActivityRole;
use App\Models\User;
use App\Services\JWTService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function jwtToken(User $user): string
{
    $user->load('roles');

    return app(JWTService::class)->generateAccessToken($user);
}

function asUser(User $user): array
{
    return ['Authorization' => 'Bearer '.jwtToken($user)];
}

/**
 * @param  array<int, ActivityPermissionEnum|string>  $permissions
 */
function attachCollaborator(
    Activity $activity,
    User $user,
    array $permissions = [],
    CollaboratorStatusEnum $status = CollaboratorStatusEnum::ACCEPTED
): void {
    $roleId = null;

    if ($permissions !== []) {
        $role = ActivityRole::factory()->create(['activity_id' => $activity->id]);

        foreach ($permissions as $permission) {
            $role->permissions()->create([
                'permission' => $permission instanceof ActivityPermissionEnum ? $permission->value : $permission,
            ]);
        }

        $roleId = $role->id;
    }

    $activity->collaboratorLinks()->create([
        'user_id' => $user->id,
        'status' => $status->value,
        'role_id' => $roleId,
        'invited_at' => now(),
        'responded_at' => $status === CollaboratorStatusEnum::PENDING ? null : now(),
    ]);
}
