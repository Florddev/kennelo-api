<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthTokenResource;
use App\Models\User;
use App\Services\JWTService;
use App\Services\MagicLinkService;
use App\Services\PasswordExpirationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Auth
 */
class MagicLinkController extends Controller
{
    public function __construct(
        private readonly JWTService $jwtService,
        private readonly PasswordExpirationService $passwordExpirationService,
        private readonly MagicLinkService $magicLinkService
    ) {}

    /**
     * Send a magic link
     *
     * @unauthenticated
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $this->magicLinkService->send((string) $request->string('email'));

        return response()->json([
            'message' => 'If an account exists for this email, a login link has been sent.',
        ]);
    }

    /**
     * Verify a magic link
     *
     * @unauthenticated
     */
    public function verify(string $id, Request $request): JsonResponse
    {
        $user = User::findOrFail($id);

        $used = $this->magicLinkService->markAsUsed(
            $user,
            (string) $request->query('expires'),
            (string) $request->query('signature'),
        );

        abort_unless($used, 410, 'This login link has already been used or has expired.');

        $user->load('roles');

        if ($user->two_factor_confirmed_at !== null) {
            return response()->json([
                'two_factor' => true,
                'challenge_token' => $this->jwtService->generateChallengeToken($user),
            ]);
        }

        $expiredChallengeToken = $this->passwordExpirationService->challengeTokenIfExpired($user);

        if ($expiredChallengeToken !== null) {
            return response()->json([
                'password_expired' => true,
                'challenge_token' => $expiredChallengeToken,
            ]);
        }

        $accessToken = $this->jwtService->generateAccessToken($user);
        $refreshToken = $this->jwtService->generateRefreshToken($user);

        return (new AuthTokenResource($user, $accessToken, $refreshToken))
            ->response();
    }
}
