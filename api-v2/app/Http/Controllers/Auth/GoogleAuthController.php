<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GoogleAuthRequest;
use App\Models\User;
use App\Services\AuthenticationService;
use App\Services\GoogleAuthService;
use App\Services\MediaService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * @tags Auth
 */
class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $authentication,
        private readonly GoogleAuthService $googleAuthService,
    ) {}

    /**
     * Login or register with Google
     *
     * Avec device_name, renvoie { user, token } au lieu d'ouvrir une session, comme la connexion par mot de passe.
     *
     * @unauthenticated
     */
    public function store(GoogleAuthRequest $request): JsonResponse
    {
        $googleUser = $this->googleAuthService->userFromToken((string) $request->string('token'));

        abort_if($googleUser === null, 401, __('login.google_failed'));

        $raw = $googleUser->getRaw();

        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $existingByEmail = User::where('email', $googleUser->getEmail())->first();

            if ($existingByEmail) {
                abort_if($existingByEmail->hasVerifiedEmail(), 409, __('login.google_email_taken'));

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

        return $this->authentication->proceed($request, $user);
    }
}
