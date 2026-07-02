<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\AdminUpdateUserRequest;
use App\Http\Requests\User\AssignRolesRequest;
use App\Http\Requests\User\ListUsersRequest;
use App\Http\Requests\User\ReviewIdentityVerificationRequest;
use App\Http\Requests\User\UpdateUserStatusRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\User\Exceptions\UserHasActiveBookingsException;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @tags Admin Users
 */
class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function index(ListUsersRequest $request): JsonResponse
    {
        $users = $this->userService->getAllPaginated($request->validated());

        return UserResource::collection($users)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function show(string $id): JsonResponse
    {
        $target = $this->resolveUser($id);

        if ($target instanceof JsonResponse) {
            return $target;
        }

        return (new UserResource($target))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function update(AdminUpdateUserRequest $request, string $id): JsonResponse
    {
        $target = $this->resolveUser($id);

        if ($target instanceof JsonResponse) {
            return $target;
        }

        $user = $this->userService->updateProfile($target, $request->validated());

        return (new UserResource($user))
            ->additional([
                'message' => 'User updated successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function destroy(string $id): JsonResponse
    {
        $target = $this->resolveUser($id);

        if ($target instanceof JsonResponse) {
            return $target;
        }

        try {
            $this->userService->deleteAccount($target);

            return response()->json([
                'message' => 'User deleted successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ]);
        } catch (UserHasActiveBookingsException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => ApiStatusEnum::ERROR,
                'timestamp' => human_date(Carbon::now()),
            ], 422);
        }
    }

    public function updateStatus(UpdateUserStatusRequest $request, string $id): JsonResponse
    {
        $target = $this->resolveUser($id);

        if ($target instanceof JsonResponse) {
            return $target;
        }

        $this->authorize('updateStatus', $target);

        $user = $this->userService->updateStatus($target, $request->validated());

        return (new UserResource($user))
            ->additional([
                'message' => 'User status updated successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function assignRoles(AssignRolesRequest $request, string $id): JsonResponse
    {
        $target = $this->resolveUser($id);

        if ($target instanceof JsonResponse) {
            return $target;
        }

        $user = $this->userService->assignRoles($target, $request->validated());

        return (new UserResource($user))
            ->additional([
                'message' => 'Roles assigned successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function removeRole(string $id, string $role): JsonResponse
    {
        $target = $this->resolveUser($id);

        if ($target instanceof JsonResponse) {
            return $target;
        }

        $user = $this->userService->removeRole($target, $role);

        return (new UserResource($user))
            ->additional([
                'message' => 'Role removed successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function reviewIdentityVerification(ReviewIdentityVerificationRequest $request, string $id): JsonResponse
    {
        $target = $this->resolveUser($id);

        if ($target instanceof JsonResponse) {
            return $target;
        }

        $user = $this->userService->reviewIdentityVerification($target, $request->user(), $request->validated());

        return (new UserResource($user))
            ->additional([
                'message' => 'Identity verification reviewed successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    private function resolveUser(string $id): User|JsonResponse
    {
        if (! Str::isUuid($id)) {
            return response()->json([
                'message' => 'Invalid UUID format',
                'status' => ApiStatusEnum::ERROR,
                'timestamp' => human_date(Carbon::now()),
            ], 400);
        }

        $user = $this->userService->getPublicProfile($id);

        if (! $user) {
            return response()->json([
                'message' => 'User not found',
                'status' => ApiStatusEnum::ERROR,
                'timestamp' => human_date(Carbon::now()),
            ], 404);
        }

        return $user;
    }
}
