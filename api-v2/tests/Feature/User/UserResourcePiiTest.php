<?php

declare(strict_types=1);

use App\Enums\UserStatusEnum;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

function renderUserResource(User $target, ?User $viewer): array
{
    $request = Request::create('/');
    $request->setUserResolver(fn () => $viewer);

    if ($viewer !== null) {
        auth()->setUser($viewer);
    }

    /** @var array{data: array<string, mixed>} $decoded */
    $decoded = json_decode(
        (new UserResource($target))->response($request)->getContent(),
        true,
    );

    return $decoded['data'];
}

it('exposes email and phone to the user themselves', function () {
    $user = User::factory()->create(['email' => 'self@example.com', 'phone' => '+33611111111']);

    $data = renderUserResource($user, $user);

    expect($data['email'])->toBe('self@example.com');
    expect($data['phone'])->toBe('+33611111111');
});

it('hides email and phone from another non-admin user', function () {
    $target = User::factory()->create(['email' => 'target@example.com', 'phone' => '+33622222222']);
    $viewer = User::factory()->create();

    $data = renderUserResource($target, $viewer);

    expect($data)->not->toHaveKey('email');
    expect($data)->not->toHaveKey('phone');
});

it('exposes email and phone to an admin viewer', function () {
    $target = User::factory()->create([
        'email' => 'target2@example.com',
        'phone' => '+33633333333',
        'status' => UserStatusEnum::ACTIVE,
    ]);
    $admin = adminUser();

    $data = renderUserResource($target, $admin);

    expect($data['email'])->toBe('target2@example.com');
    expect($data['phone'])->toBe('+33633333333');
});
