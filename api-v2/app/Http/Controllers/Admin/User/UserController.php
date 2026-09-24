<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\User;

use App\Enums\AdminActionTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\BanRequest;
use App\Http\Requests\User\AdminUpdateUserRequest;
use App\Http\Requests\User\AssignRolesRequest;
use App\Http\Requests\User\ListUsersRequest;
use App\Http\Requests\User\UpdateUserStatusRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Admin\AdminActionService;
use App\Services\Admin\User\AdminUserService;
use App\Services\User\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * @tags Admin Users
 *
 * {user} inclut les comptes inactifs ou bannis (liaison déclarée dans AppServiceProvider).
 */
class UserController extends Controller
{
    public function __construct(
        private UserService $userService,
        private AdminUserService $adminUserService,
        private AdminActionService $actions
    ) {}

    public function index(ListUsersRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        return UserResource::collection($this->userService->getAllPaginated($request->validated()));
    }

    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return new UserResource($user->load('roles'));
    }

    public function update(AdminUpdateUserRequest $request, User $user): UserResource
    {
        $this->authorize('update', $user);

        return new UserResource($this->userService->updateProfile($user, $request->validated()));
    }

    public function destroy(Request $request, User $user): Response
    {
        $this->authorize('destroy', $user);

        $this->userService->deleteAccount($user);
        $this->actions->log($request->user(), $user, AdminActionTypeEnum::DELETE);

        return response()->noContent();
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user): UserResource
    {
        $this->authorize('updateStatus', $user);

        $updated = $this->userService->updateStatus($user, $request->validated());
        $this->actions->log($request->user(), $user, AdminActionTypeEnum::UPDATE_STATUS, $request->validated());

        return new UserResource($updated);
    }

    public function assignRoles(AssignRolesRequest $request, User $user): UserResource
    {
        $this->authorize('assignRoles', $user);

        $updated = $this->userService->assignRoles($user, $request->validated());
        $this->actions->log($request->user(), $user, AdminActionTypeEnum::ASSIGN_ROLES, $request->validated());

        return new UserResource($updated);
    }

    public function removeRole(Request $request, User $user, string $role): UserResource
    {
        $this->authorize('assignRoles', $user);

        $updated = $this->userService->removeRole($user, $role);
        $this->actions->log($request->user(), $user, AdminActionTypeEnum::REMOVE_ROLE, ['role' => $role]);

        return new UserResource($updated);
    }

    public function ban(BanRequest $request, User $user): UserResource
    {
        $this->authorize('ban', $user);

        $updated = $this->adminUserService->ban($user, $request->user(), $request->validated());
        $this->actions->log($request->user(), $user, AdminActionTypeEnum::BAN, $request->validated());

        return new UserResource($updated);
    }

    public function unban(Request $request, User $user): UserResource
    {
        $this->authorize('unban', $user);

        $updated = $this->adminUserService->unban($user);
        $this->actions->log($request->user(), $user, AdminActionTypeEnum::UNBAN);

        return new UserResource($updated);
    }

    public function forcePasswordReset(Request $request, User $user): Response
    {
        $this->authorize('forcePasswordReset', $user);

        $this->adminUserService->forcePasswordReset($user);
        $this->actions->log($request->user(), $user, AdminActionTypeEnum::FORCE_PASSWORD_RESET);

        return response()->noContent();
    }

    public function verifyEmail(Request $request, User $user): UserResource
    {
        $this->authorize('verifyEmail', $user);

        $updated = $this->adminUserService->verifyEmail($user);
        $this->actions->log($request->user(), $user, AdminActionTypeEnum::VERIFY_EMAIL);

        return new UserResource($updated);
    }

    public function resendVerification(Request $request, User $user): Response
    {
        $this->authorize('verifyEmail', $user);

        $this->adminUserService->resendVerification($user);
        $this->actions->log($request->user(), $user, AdminActionTypeEnum::RESEND_VERIFICATION);

        return response()->noContent();
    }
}
