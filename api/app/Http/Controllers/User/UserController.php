<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\ChangeEmailRequest;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\SubmitIdentityVerificationRequest;
use App\Http\Requests\User\UpdateLocaleRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Requests\User\UploadAvatarRequest;
use App\Http\Requests\User\UpsertAddressRequest;
use App\Http\Resources\AddressResource;
use App\Http\Resources\IdentityVerificationResource;
use App\Http\Resources\UserResource;
use App\Services\User\Exceptions\InvalidCurrentPasswordException;
use App\Services\User\Exceptions\UserHasActiveBookingsException;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @tags Users
 */
class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {}

    public function show(string $id): JsonResponse
    {
        if (! Str::isUuid($id)) {
            return response()->json([
                'message' => 'Invalid UUID format',
                'status' => ApiStatusEnum::ERROR,
                'timestamp' => human_date(now()),
            ], 400);
        }

        $user = $this->userService->getPublicProfile($id);

        if (! $user) {
            return response()->json([
                'message' => 'User not found',
                'status' => ApiStatusEnum::ERROR,
                'timestamp' => human_date(now()),
            ], 404);
        }

        $this->authorize('view', $user);

        return (new UserResource($user))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function getCurrentUser(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['roles', 'address', 'subscription.plan']);

        return (new UserResource($user))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $updatedUser = $this->userService->updateProfile($user, $request->validated());

        return (new UserResource($updatedUser))
            ->additional([
                'message' => 'Profile updated successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = $request->user();
        $updatedUser = $this->userService->uploadAvatar($user, $request->file('avatar'));

        return (new UserResource($updatedUser))
            ->additional([
                'message' => 'Avatar uploaded successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function updateLocale(UpdateLocaleRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update(['locale' => $request->validated('locale')]);

        return response()->json([
            'success' => true,
            'locale' => $user->locale,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $this->userService->deleteAccount($user);

            return response()->json([
                'message' => 'Account deleted successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ]);
        } catch (UserHasActiveBookingsException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => ApiStatusEnum::ERROR,
                'timestamp' => human_date(now()),
            ], 422);
        }
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $this->userService->changePassword($request->user(), $request->validated());

            return response()->json([
                'message' => 'Password changed successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ]);
        } catch (InvalidCurrentPasswordException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => ApiStatusEnum::ERROR,
                'timestamp' => human_date(now()),
            ], 422);
        }
    }

    public function changeEmail(ChangeEmailRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->changeEmail($request->user(), $request->validated());

            return (new UserResource($user))
                ->additional([
                    'message' => 'Email updated successfully. Please verify your new email.',
                    'status' => ApiStatusEnum::SUCCESS,
                    'timestamp' => human_date(now()),
                ])
                ->response();
        } catch (InvalidCurrentPasswordException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => [
                    'password' => [$e->getMessage()],
                ],
                'status' => ApiStatusEnum::ERROR,
                'timestamp' => human_date(now()),
            ], 422);
        }
    }

    public function upsertAddress(UpsertAddressRequest $request): JsonResponse
    {
        $user = $this->userService->upsertAddress($request->user(), $request->validated());

        return (new AddressResource($user->address))
            ->additional([
                'message' => 'Address updated successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function destroyAddress(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->address_id) {
            return response()->json([
                'message' => 'No address to delete',
                'status' => ApiStatusEnum::ERROR,
                'timestamp' => human_date(now()),
            ], 404);
        }

        $this->userService->deleteAddress($user);

        return response()->json([
            'message' => 'Address deleted successfully',
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }

    public function getIdentityVerification(Request $request): JsonResponse
    {
        $verification = $this->userService->getLatestIdentityVerification($request->user());

        if (! $verification) {
            return response()->json([
                'data' => null,
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ]);
        }

        return (new IdentityVerificationResource($verification))
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function submitIdentityVerification(SubmitIdentityVerificationRequest $request): JsonResponse
    {
        $verification = $this->userService->submitIdentityVerification(
            $request->user(),
            $request->file('document')
        );

        return (new IdentityVerificationResource($verification))
            ->additional([
                'message' => 'Identity verification submitted successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response()
            ->setStatusCode(201);
    }
}
