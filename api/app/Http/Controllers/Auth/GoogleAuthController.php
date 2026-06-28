<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GoogleAuthRequest;
use App\Http\Resources\AuthTokenResource;
use App\Models\User;
use App\Services\JWTService;
use App\Services\MediaService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;

/**
 * @tags Auth
 */
class GoogleAuthController extends Controller
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
     * Login or register with Google
     *
     * @unauthenticated
     */
    public function store(GoogleAuthRequest $request): JsonResponse
    {
        $driver = Socialite::driver('google');

        if (! $driver instanceof AbstractProvider) {
            abort(500, 'Google authentication is not configured.');
        }

        try {
            $googleUser = $driver->stateless()->userFromToken((string) $request->string('token'));
        } catch (\Throwable $e) {
            Log::warning('Google authentication failed: '.$e->getMessage());
            abort(401, 'Unable to authenticate with Google.');
        }

        $raw = $googleUser->getRaw();

        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $existingByEmail = User::where('email', $googleUser->getEmail())->first();

            if ($existingByEmail) {
                if ($existingByEmail->hasVerifiedEmail()) {
                    abort(409, 'An account with this email already exists. Please sign in with your password.');
                }

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

        $accessToken = $this->jwtService->generateAccessToken($user);
        $refreshToken = $this->jwtService->generateRefreshToken($user);

        return (new AuthTokenResource($user, $accessToken, $refreshToken))
            ->response()
            ->setStatusCode(200);
    }
}
