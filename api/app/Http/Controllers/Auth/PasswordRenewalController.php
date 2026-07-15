<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RenewPasswordRequest;
use App\Http\Resources\AuthTokenResource;
use App\Services\JWTService;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;

/**
 * @tags Auth
 */
class PasswordRenewalController extends Controller
{
    public function __construct(
        private readonly JWTService $jwtService,
        private readonly UserService $userService
    ) {}

    /**
     * Renew an expired password
     *
     * @unauthenticated
     */
    public function store(RenewPasswordRequest $request): JsonResponse
    {
        $challengeToken = (string) $request->validated('challenge_token');

        try {
            $user = $this->jwtService->validatePasswordResetChallengeToken($challengeToken);
        } catch (\Throwable) {
            abort(401, 'Invalid or expired challenge token.');
        }

        $this->userService->renewExpiredPassword($user, (string) $request->validated('password'));

        $this->jwtService->blacklistToken($challengeToken);

        $user->load('roles');

        $accessToken = $this->jwtService->generateAccessToken($user);
        $refreshToken = $this->jwtService->generateRefreshToken($user);

        return (new AuthTokenResource($user, $accessToken, $refreshToken))
            ->response();
    }
}
