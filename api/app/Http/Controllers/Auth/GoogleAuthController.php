<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GoogleAuthRequest;
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

        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            if (! $user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }
        } else {
            $raw = $googleUser->getRaw();

            $user = User::create([
                'first_name' => $raw['given_name'] ?? $googleUser->getName() ?? '',
                'last_name' => $raw['family_name'] ?? '',
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'email_verified_at' => now(),
                'locale' => $request->locale ?? config('app.locale', 'en'),
            ]);

            $user->assignRole('user');

            event(new Registered($user));
        }

        $avatarUrl = $googleUser->getAvatar();

        if ($avatarUrl && ! $user->getFirstMedia(MediaService::COLLECTION_AVATAR)) {
            try {
                $contents = Http::get($avatarUrl)->throw()->body();

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

        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'locale' => $user->locale,
                'is_id_verified' => $user->is_id_verified,
                'email_verified_at' => $user->email_verified_at,
                'roles' => $user->roles->pluck('name'),
            ],
        ]);
    }
}
