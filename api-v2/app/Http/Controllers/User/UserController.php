<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ChangeEmailRequest;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\UpdateLocaleRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Requests\User\UploadAvatarRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @tags Users
 */
class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return new UserResource($user->load('roles'));
    }

    public function getCurrentUser(Request $request): UserResource
    {
        return new UserResource($request->user()->load('roles'));
    }

    public function updateProfile(UpdateProfileRequest $request): UserResource
    {
        return new UserResource($this->userService->updateProfile($request->user(), $request->validated()));
    }

    public function uploadAvatar(UploadAvatarRequest $request): UserResource
    {
        return new UserResource($this->userService->uploadAvatar($request->user(), $request->file('avatar')));
    }

    public function updateLocale(UpdateLocaleRequest $request): UserResource
    {
        $user = $request->user();
        $user->update(['locale' => $request->validated('locale')]);

        return new UserResource($user->load('roles'));
    }

    public function destroy(Request $request): Response
    {
        $this->userService->deleteAccount($request->user());

        return response()->noContent();
    }

    public function changePassword(ChangePasswordRequest $request): Response
    {
        $this->userService->changePassword($request->user(), $request->validated());

        return response()->noContent();
    }

    public function changeEmail(ChangeEmailRequest $request): UserResource
    {
        return new UserResource($this->userService->changeEmail($request->user(), $request->validated()));
    }
}
