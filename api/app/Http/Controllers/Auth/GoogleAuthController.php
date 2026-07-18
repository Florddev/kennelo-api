<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GoogleAuthRequest;
use App\Http\Resources\AuthTokenResource;
use App\Models\User;
use App\Services\GoogleAuthService;
use App\Services\JWTService;
use App\Services\MediaService;
use App\Services\PasswordExpirationService;
use App\Services\TwoFactorService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * @tags Auth
 */
class GoogleAuthController extends Controller
{
    /**
     * The JWT service instance.
     */
    protected JWTService $jwtService;

    protected TwoFactorService $twoFactorService;

    protected GoogleAuthService $googleAuthService;

    protected PasswordExpirationService $passwordExpirationService;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        JWTService $jwtService,
        TwoFactorService $twoFactorService,
        GoogleAuthService $googleAuthService,
        PasswordExpirationService $passwordExpirationService
    ) {
        $this->jwtService = $jwtService;
        $this->twoFactorService = $twoFactorService;
        $this->googleAuthService = $googleAuthService;
        $this->passwordExpirationService = $passwordExpirationService;
    }

    /**
     * Login or register with Google
     *
     * @unauthenticated
     */
    public function store(GoogleAuthRequest $request): JsonResponse
    {
        $googleUser = $this->googleAuthService->userFromToken((string) $request->string('token'));

        abort_if($googleUser === null, 401, 'Unable to authenticate with Google.');

        $raw = $googleUser->getRaw();

        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $existingByEmail = User::where('email', $googleUser->getEmail())->first();

            if ($existingByEmail) {
                abort_if($existingByEmail->hasVerifiedEmail(), 409, 'An account with this email already exists. Please sign in with your password.');

                $existingByEmail->update(['google_id' => $googleUser->getId()]);
                $existingByEmail->markEmailAsVerified();

                $user = $existingByEmail;
            }
        }

        if (! $user) {
            $user = User::create([
                'first_name' => $raw['given_name'] ?? $googleUser->getName() ?? '',
                'last_name' => $raw['family_name'] ?? '',
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'locale' => $request->validated('locale') ?? config('app.locale', 'en'),
            ]);

            $user->markEmailAsVerified();
            $user->assignRole('user');

            event(new Registered($user));
        }

        $avatarUrl = $googleUser->getAvatar();

        if ($avatarUrl && ! $user->getFirstMedia(MediaService::COLLECTION_AVATAR)) {
            try {
                $contents = Http::timeout(5)->get($avatarUrl)->throw()->body();

                $user->addMediaFromString($contents)
                    ->usingFileName('avatar.jpg')
                    ->toMediaCollection(MediaService::COLLECTION_AVATAR);
            } catch (\Throwable $e) {
                Log::warning('Failed to import Google avatar: '.$e->getMessage());
            }
        }

        $user->load('roles');

        if ($user->two_factor_confirmed_at !== null
            && ! $this->twoFactorService->deviceIsRemembered($user, $request->string('remember_token')->toString())) {
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
            ->response()
            ->setStatusCode(200);
    }
}
