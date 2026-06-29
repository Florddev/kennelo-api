<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmTwoFactorRequest;
use App\Http\Requests\Auth\TwoFactorStepUpRequest;
use App\Models\User;
use App\Notifications\TwoFactorStatusNotification;
use App\Services\GoogleAuthService;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @tags Auth
 */
class TwoFactorAuthenticationController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactorService,
        private readonly GoogleAuthService $googleAuthService,
    ) {}

    /**
     * Enable two-factor authentication
     */
    public function store(TwoFactorStepUpRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->two_factor_confirmed_at !== null) {
            abort(409, 'Two-factor authentication is already enabled.');
        }

        $this->ensureStepUp($user, $request);

        $secret = $this->twoFactorService->generateSecret();

        $user->two_factor_secret = $secret;
        $user->save();

        return response()->json([
            'qr_svg' => $this->twoFactorService->qrCodeDataUri($user->email, $secret),
            'secret' => $secret,
        ]);
    }

    /**
     * Confirm and activate two-factor authentication
     */
    public function confirm(ConfirmTwoFactorRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->two_factor_secret === null) {
            abort(409, 'Two-factor authentication has not been initiated.');
        }

        if (! $this->twoFactorService->verify($user->two_factor_secret, (string) $request->validated('code'))) {
            throw ValidationException::withMessages(['code' => 'The provided two-factor code is invalid.']);
        }

        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();

        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->two_factor_confirmed_at = now();
        $user->save();

        $user->notify(new TwoFactorStatusNotification(true));

        return response()->json(['recovery_codes' => $recoveryCodes]);
    }

    /**
     * Disable two-factor authentication
     */
    public function destroy(TwoFactorStepUpRequest $request): Response
    {
        $user = $request->user();

        $this->ensureStepUp($user, $request);

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        $user->rememberedDevices()->delete();

        $user->notify(new TwoFactorStatusNotification(false));

        return response()->noContent();
    }

    /**
     * Regenerate recovery codes
     */
    public function recoveryCodes(TwoFactorStepUpRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->two_factor_confirmed_at === null) {
            abort(409, 'Two-factor authentication is not enabled.');
        }

        $this->ensureStepUp($user, $request);

        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();

        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->save();

        return response()->json(['recovery_codes' => $recoveryCodes]);
    }

    private function ensureStepUp(User $user, TwoFactorStepUpRequest $request): void
    {
        if ($user->password !== null) {
            if (! Hash::check((string) $request->validated('password'), $user->password)) {
                throw ValidationException::withMessages(['password' => 'The provided password is incorrect.']);
            }

            return;
        }

        $googleUser = $this->googleAuthService->userFromToken((string) $request->validated('google_token'));

        if ($user->google_id === null || $googleUser === null || $googleUser->getId() !== $user->google_id) {
            throw ValidationException::withMessages(['google_token' => 'Google re-authentication failed.']);
        }
    }
}
