<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Fin de connexion commune à tous les modes (mot de passe, Google, lien magique, inscription) :
 * double authentification, puis renouvellement d'un mot de passe expiré, puis ouverture de la session,
 * ou émission d'un token personnel Sanctum quand la requête porte un device_name.
 *
 * Entre deux étapes, l'utilisateur en attente est gardé sans être connecté : en session, ou en cache sous
 * le pending_token renvoyé au client.
 */
class AuthenticationService
{
    private const string PENDING_TWO_FACTOR = 'auth.pending.two_factor';

    private const string PENDING_PASSWORD_RENEWAL = 'auth.pending.password_renewal';

    public function __construct(
        private readonly TwoFactorService $twoFactorService,
        private readonly PasswordExpirationService $passwordExpirationService,
    ) {}

    public function proceed(Request $request, User $user, bool $remember = false, bool $trustRememberedDevice = true): JsonResponse
    {
        if ($this->requiresTwoFactor($request, $user, $trustRememberedDevice)) {
            return response()->json([
                'two_factor' => true,
                'pending_token' => $this->hold($request, self::PENDING_TWO_FACTOR, $user, $remember),
            ]);
        }

        return $this->afterTwoFactor($request, $user, $remember);
    }

    public function afterTwoFactor(Request $request, User $user, bool $remember = false): JsonResponse
    {
        if ($this->passwordExpirationService->isExpired($user)) {
            return response()->json([
                'password_expired' => true,
                'pending_token' => $this->hold($request, self::PENDING_PASSWORD_RENEWAL, $user, $remember),
            ]);
        }

        return $this->login($request, $user, $remember);
    }

    public function login(Request $request, User $user, bool $remember = false, int $status = 200): JsonResponse
    {
        $deviceName = $this->deviceName($request);

        $this->forgetPending($request);

        if ($deviceName !== null) {
            Auth::guard('web')->setUser($user);

            return response()->json([
                'user' => new UserResource($user->load('roles')),
                'token' => $user->createToken($deviceName)->plainTextToken,
            ], $status);
        }

        Auth::guard('web')->login($user, $remember);

        $request->session()->regenerate();

        return (new UserResource($user->load('roles')))
            ->response()
            ->setStatusCode($status);
    }

    public function logout(Request $request): void
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();

            return;
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * @return array{user: User, remember: bool}|null
     */
    public function pendingTwoFactor(Request $request): ?array
    {
        return $this->pending($request, self::PENDING_TWO_FACTOR);
    }

    /**
     * @return array{user: User, remember: bool}|null
     */
    public function pendingPasswordRenewal(Request $request): ?array
    {
        return $this->pending($request, self::PENDING_PASSWORD_RENEWAL);
    }

    public function forgetPending(Request $request): void
    {
        if ($this->deviceName($request) === null) {
            $request->session()->forget([self::PENDING_TWO_FACTOR, self::PENDING_PASSWORD_RENEWAL]);

            return;
        }

        $pendingToken = $request->string('pending_token')->toString();

        if ($pendingToken !== '') {
            Cache::deleteMultiple([
                $this->pendingCacheKey(self::PENDING_TWO_FACTOR, $pendingToken),
                $this->pendingCacheKey(self::PENDING_PASSWORD_RENEWAL, $pendingToken),
            ]);
        }
    }

    private function deviceName(Request $request): ?string
    {
        if ($request->filled('device_name')) {
            return $request->string('device_name')->toString();
        }

        if (! $request->hasSession()) {
            $request->validate(['device_name' => ['required']]);
        }

        return null;
    }

    private function requiresTwoFactor(Request $request, User $user, bool $trustRememberedDevice): bool
    {
        if ($user->two_factor_confirmed_at === null) {
            return false;
        }

        return ! $trustRememberedDevice
            || ! $this->twoFactorService->deviceIsRemembered($user, $request->string('remember_token')->toString());
    }

    private function hold(Request $request, string $key, User $user, bool $remember): ?string
    {
        $this->forgetPending($request);

        $pending = [
            'id' => $user->getKey(),
            'remember' => $remember,
            'at' => now()->getTimestamp(),
        ];

        if ($this->deviceName($request) === null) {
            $request->session()->put($key, $pending);

            return null;
        }

        $pendingToken = Str::random(64);

        Cache::put($this->pendingCacheKey($key, $pendingToken), $pending, now()->addMinutes($this->pendingTtl($key)));

        return $pendingToken;
    }

    /**
     * @return array{user: User, remember: bool}|null
     */
    private function pending(Request $request, string $key): ?array
    {
        $pending = $this->deviceName($request) === null
            ? $request->session()->get($key)
            : Cache::get($this->pendingCacheKey($key, $request->string('pending_token')->toString()));

        if (! is_array($pending) || now()->getTimestamp() - (int) $pending['at'] > $this->pendingTtl($key) * 60) {
            $this->forgetPending($request);

            return null;
        }

        $user = User::find($pending['id']);

        if ($user === null) {
            $this->forgetPending($request);

            return null;
        }

        return ['user' => $user, 'remember' => (bool) $pending['remember']];
    }

    private function pendingTtl(string $key): int
    {
        return (int) config($key === self::PENDING_TWO_FACTOR ? 'auth.two_factor_challenge_ttl' : 'auth.password_renewal_ttl');
    }

    private function pendingCacheKey(string $key, string $pendingToken): string
    {
        return $key.':'.hash('sha256', $pendingToken);
    }
}
