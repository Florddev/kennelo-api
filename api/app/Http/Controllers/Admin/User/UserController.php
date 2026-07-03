<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\User;

use App\Enums\AdminActionTypeEnum;
use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\BanRequest;
use App\Http\Requests\User\AdminUpdateUserRequest;
use App\Http\Requests\User\AssignRolesRequest;
use App\Http\Requests\User\ListUsersRequest;
use App\Http\Requests\User\ReviewIdentityVerificationRequest;
use App\Http\Requests\User\UpdateUserStatusRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Admin\AdminActionService;
use App\Services\Admin\User\AdminUserService;
use App\Services\User\Exceptions\UserHasActiveBookingsException;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @tags Admin Users
 */
class UserController extends Controller
{
    public function __construct(
        private UserService $userService,
        private AdminUserService $adminUserService,
        private AdminActionService $actions
    ) {}

    public function index(ListUsersRequest $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

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

        $this->authorize('view', $target);

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

        $this->authorize('update', $target);

        $user = $this->userService->updateProfile($target, $request->validated());

        return (new UserResource($user))
            ->additional([
                'message' => 'User updated successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $target = $this->resolveUser($id);

        if ($target instanceof JsonResponse) {
            return $target;
        }

        $this->authorize('destroy', $target);

        try {
            $this->userService->deleteAccount($target);
            $this->actions->log($request->user(), $target, AdminActionTypeEnum::DELETE);

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
        $this->actions->log($request->user(), $target, AdminActionTypeEnum::UPDATE_STATUS, $request->validated());

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

        $this->authorize('assignRoles', $target);

        $user = $this->userService->assignRoles($target, $request->validated());
        $this->actions->log($request->user(), $target, AdminActionTypeEnum::ASSIGN_ROLES, $request->validated());

        return (new UserResource($user))
            ->additional([
                'message' => 'Roles assigned successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function removeRole(Request $request, string $id, string $role): JsonResponse
    {
        $target = $this->resolveUser($id);

        if ($target instanceof JsonResponse) {
            return $target;
        }

        $this->authorize('assignRoles', $target);

        $user = $this->userService->removeRole($target, $role);
        $this->actions->log($request->user(), $target, AdminActionTypeEnum::REMOVE_ROLE, ['role' => $role]);

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

        $this->authorize('reviewIdentityVerification', $target);

        $user = $this->userService->reviewIdentityVerification($target, $request->user(), $request->validated());
        $this->actions->log($request->user(), $target, AdminActionTypeEnum::REVIEW_IDENTITY, $request->validated());

        return (new UserResource($user))
            ->additional([
                'message' => 'Identity verification reviewed successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function ban(BanRequest $request, string $id): JsonResponse
    {
        $target = $this->resolveUser($id);

        if ($target instanceof JsonResponse) {
            return $target;
        }

        $this->authorize('ban', $target);

        $user = $this->adminUserService->ban($target, $request->user(), $request->validated());
        $this->actions->log($request->user(), $target, AdminActionTypeEnum::BAN, $request->validated());

        return (new UserResource($user))
            ->additional([
                'message' => 'User banned successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function unban(Request $request, string $id): JsonResponse
    {
        $target = $this->resolveUser($id);

        if ($target instanceof JsonResponse) {
            return $target;
        }

        $this->authorize('unban', $target);

        $user = $this->adminUserService->unban($target);
        $this->actions->log($request->user(), $target, AdminActionTypeEnum::UNBAN);

        return (new UserResource($user))
            ->additional([
                'message' => 'User unbanned successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function forcePasswordReset(Request $request, string $id): JsonResponse
    {
        $target = $this->resolveUser($id);

        if ($target instanceof JsonResponse) {
            return $target;
        }

        $this->authorize('forcePasswordReset', $target);

        $this->adminUserService->forcePasswordReset($target);
        $this->actions->log($request->user(), $target, AdminActionTypeEnum::FORCE_PASSWORD_RESET);

        return response()->json([
            'message' => 'Password reset link sent successfully',
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(Carbon::now()),
        ]);
    }

    public function verifyEmail(Request $request, string $id): JsonResponse
    {
        $target = $this->resolveUser($id);

        if ($target instanceof JsonResponse) {
            return $target;
        }

        $this->authorize('verifyEmail', $target);

        $user = $this->adminUserService->verifyEmail($target);
        $this->actions->log($request->user(), $target, AdminActionTypeEnum::VERIFY_EMAIL);

        return (new UserResource($user))
            ->additional([
                'message' => 'Email verified successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(Carbon::now()),
            ])
            ->response();
    }

    public function resendVerification(Request $request, string $id): JsonResponse
    {
        $target = $this->resolveUser($id);

        if ($target instanceof JsonResponse) {
            return $target;
        }

        $this->authorize('verifyEmail', $target);

        $this->adminUserService->resendVerification($target);
        $this->actions->log($request->user(), $target, AdminActionTypeEnum::RESEND_VERIFICATION);

        return response()->json([
            'message' => 'Verification email sent successfully',
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(Carbon::now()),
        ]);
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
