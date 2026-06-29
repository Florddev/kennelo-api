<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\TwoFactorChallengeRequest;
use App\Http\Resources\AuthTokenResource;
use App\Services\JWTService;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * @tags Auth
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * The JWT service instance.
     */
    protected JWTService $jwtService;

    protected TwoFactorService $twoFactorService;

    /**
     * Create a new controller instance.
     */
    public function __construct(JWTService $jwtService, TwoFactorService $twoFactorService)
    {
        $this->jwtService = $jwtService;
        $this->twoFactorService = $twoFactorService;
    }

    /**
     * Login
     *
     * @unauthenticated
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();

        $user = $request->user();

        $user->load('roles');

        if ($user->two_factor_confirmed_at !== null
            && ! $this->twoFactorService->deviceIsRemembered($user, $request->string('remember_token')->toString())) {
            return response()->json([
                'two_factor' => true,
                'challenge_token' => $this->jwtService->generateChallengeToken($user),
            ]);
        }

        $accessToken = $this->jwtService->generateAccessToken($user);
        $refreshToken = $this->jwtService->generateRefreshToken($user);

        return (new AuthTokenResource($user, $accessToken, $refreshToken))
            ->response();
    }

    /**
     * Two-factor challenge
     *
     * @unauthenticated
     */
    public function twoFactorChallenge(TwoFactorChallengeRequest $request): JsonResponse
    {
        $challengeToken = (string) $request->validated('challenge_token');

        try {
            $user = $this->jwtService->validateChallengeToken($challengeToken);
        } catch (\Throwable) {
            abort(401, 'Invalid or expired challenge token.');
        }

        $code = trim((string) $request->validated('code'));

        $verified = $code !== ''
            ? $this->twoFactorService->verify((string) $user->two_factor_secret, $code)
            : $this->twoFactorService->consumeRecoveryCode(
                $user,
                strtoupper(trim((string) $request->validated('recovery_code'))),
            );

        if (! $verified) {
            throw ValidationException::withMessages(['code' => 'The provided two-factor code is invalid.']);
        }

        $rememberToken = $request->boolean('remember')
            ? $this->twoFactorService->rememberDevice($user)
            : null;

        $user->load('roles');

        $accessToken = $this->jwtService->generateAccessToken($user);
        $refreshToken = $this->jwtService->generateRefreshToken($user);

        $this->jwtService->blacklistToken($challengeToken);

        $resource = new AuthTokenResource($user, $accessToken, $refreshToken);

        if ($rememberToken !== null) {
            $resource->additional(['remember_token' => $rememberToken]);
        }

        return $resource->response();
    }

    /**
     * Refresh token
     *
     * @unauthenticated
     */
    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $request->input('refresh_token');

        if (! $refreshToken) {
            return response()->json([
                'message' => 'Refresh token is required',
            ], 400);
        }

        try {
            $result = $this->jwtService->refreshAccessToken($refreshToken);

            return response()->json([
                'access_token' => $result['access_token'],
                'token_type' => 'Bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ]);
        } catch (\Exception $e) {
            Log::error('Token refresh failed: '.$e->getMessage());

            return response()->json([
                'message' => 'Token refresh failed',
                'error' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Logout
     */
    public function destroy(Request $request): Response
    {
        $refreshToken = $request->input('refresh_token');

        if ($refreshToken) {
            try {
                $this->jwtService->blacklistToken($refreshToken);
            } catch (\Exception $e) {
                Log::warning('Failed to blacklist refresh token: '.$e->getMessage());
            }
        }

        return response()->noContent();
    }
}
