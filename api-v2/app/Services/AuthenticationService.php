<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Fin de connexion commune à tous les modes (mot de passe, Google, lien magique, inscription) :
 * double authentification, puis renouvellement d'un mot de passe expiré, puis ouverture de la session.
 *
 * Entre deux étapes, l'utilisateur en attente est gardé en session sans être connecté.
 */
class AuthenticationService
{
    private const PENDING_TWO_FACTOR = 'auth.pending.two_factor';

    private const PENDING_PASSWORD_RENEWAL = 'auth.pending.password_renewal';

    public function __construct(
        private readonly TwoFactorService $twoFactorService,
        private readonly PasswordExpirationService $passwordExpirationService,
    ) {}

    public function proceed(Request $request, User $user, bool $remember = false, bool $trustRememberedDevice = true): JsonResponse
    {
        if ($this->requiresTwoFactor($request, $user, $trustRememberedDevice)) {
            $this->hold($request, self::PENDING_TWO_FACTOR, $user, $remember);

            return response()->json(['two_factor' => true]);
        }

        return $this->afterTwoFactor($request, $user, $remember);
    }

    public function afterTwoFactor(Request $request, User $user, bool $remember = false): JsonResponse
    {
        if ($this->passwordExpirationService->isExpired($user)) {
            $this->hold($request, self::PENDING_PASSWORD_RENEWAL, $user, $remember);

            return response()->json(['password_expired' => true]);
        }

        return $this->login($request, $user, $remember);
    }

    public function login(Request $request, User $user, bool $remember = false, int $status = 200): JsonResponse
    {
        Auth::guard('web')->login($user, $remember);

        $request->session()->regenerate();
        $this->forgetPending($request);

        return (new UserResource($user->load('roles')))
            ->response()
            ->setStatusCode($status);
    }

    public function logout(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * @return array{user: User, remember: bool}|null
     */
    public function pendingTwoFactor(Request $request): ?array
    {
        return $this->pending($request, self::PENDING_TWO_FACTOR, (int) config('auth.two_factor_challenge_ttl'));
    }

    /**
     * @return array{user: User, remember: bool}|null
     */
    public function pendingPasswordRenewal(Request $request): ?array
    {
        return $this->pending($request, self::PENDING_PASSWORD_RENEWAL, (int) config('auth.password_renewal_ttl'));
    }

    public function forgetPending(Request $request): void
    {
        $request->session()->forget([self::PENDING_TWO_FACTOR, self::PENDING_PASSWORD_RENEWAL]);
    }

    private function requiresTwoFactor(Request $request, User $user, bool $trustRememberedDevice): bool
    {
        if ($user->two_factor_confirmed_at === null) {
            return false;
        }

        return ! $trustRememberedDevice
            || ! $this->twoFactorService->deviceIsRemembered($user, $request->string('remember_token')->toString());
    }

    private function hold(Request $request, string $key, User $user, bool $remember): void
    {
        $this->forgetPending($request);

        $request->session()->put($key, [
            'id' => $user->getKey(),
            'remember' => $remember,
            'at' => now()->getTimestamp(),
        ]);
    }

    /**
     * @return array{user: User, remember: bool}|null
     */
    private function pending(Request $request, string $key, int $ttlMinutes): ?array
    {
        $pending = $request->session()->get($key);

        if (! is_array($pending) || now()->getTimestamp() - (int) $pending['at'] > $ttlMinutes * 60) {
            $request->session()->forget($key);

            return null;
        }

        $user = User::find($pending['id']);

        if ($user === null) {
            $request->session()->forget($key);

            return null;
        }

        return ['user' => $user, 'remember' => (bool) $pending['remember']];
    }
}
