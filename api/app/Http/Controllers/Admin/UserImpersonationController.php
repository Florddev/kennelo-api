<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Services\Admin\ImpersonationService;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @tags Admin Impersonation
 */
class UserImpersonationController extends Controller
{
    public function __construct(
        private ImpersonationService $impersonation,
        private UserService $userService
    ) {}

    public function start(Request $request, string $id): JsonResponse
    {
        if (! Str::isUuid($id)) {
            return response()->json([
                'message' => 'Invalid UUID format',
                'status' => ApiStatusEnum::ERROR,
                'timestamp' => human_date(Carbon::now()),
            ], 400);
        }

        $target = $this->userService->getPublicProfile($id);

        if (! $target) {
            return response()->json([
                'message' => 'User not found',
                'status' => ApiStatusEnum::ERROR,
                'timestamp' => human_date(Carbon::now()),
            ], 404);
        }

        $this->authorize('impersonate', $target);

        $result = $this->impersonation->start($target, $request->user());

        return response()->json([
            'data' => [
                'token' => $result['token'],
                'expires_in' => $result['expires_in'],
                'user' => new UserResource($target),
            ],
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(Carbon::now()),
        ]);
    }

    public function stop(Request $request): JsonResponse
    {
        $payload = $request->attributes->get('jwt_payload');
        $impersonatorId = $payload->impersonator_id ?? null;

        if (! $impersonatorId) {
            return response()->json([
                'message' => 'Not an impersonation session',
                'status' => ApiStatusEnum::ERROR,
                'timestamp' => human_date(Carbon::now()),
            ], 400);
        }

        $this->impersonation->stop($request->bearerToken(), $request->user(), $impersonatorId);

        return response()->json([
            'message' => 'Impersonation stopped successfully',
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(Carbon::now()),
        ]);
    }
}
