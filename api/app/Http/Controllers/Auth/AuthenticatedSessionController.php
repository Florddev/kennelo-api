<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\AuthTokenResource;
use App\Services\JWTService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * @tags Auth
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * The JWT service instance.
     */
    protected JWTService $jwtService;

    /**
     * Create a new controller instance.
     */
    public function __construct(JWTService $jwtService)
    {
        $this->jwtService = $jwtService;
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

        $accessToken = $this->jwtService->generateAccessToken($user);
        $refreshToken = $this->jwtService->generateRefreshToken($user);

        return (new AuthTokenResource($user, $accessToken, $refreshToken))
            ->response();
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
