<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmTwoFactorRequest;
use App\Http\Requests\Auth\DisableTwoFactorRequest;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @tags Auth
 */
class TwoFactorAuthenticationController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactorService) {}

    /**
     * Enable two-factor authentication
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->two_factor_confirmed_at !== null) {
            abort(409, 'Two-factor authentication is already enabled.');
        }

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

        return response()->json(['recovery_codes' => $recoveryCodes]);
    }

    /**
     * Disable two-factor authentication
     */
    public function destroy(DisableTwoFactorRequest $request): Response
    {
        $user = $request->user();

        if (! Hash::check((string) $request->validated('password'), (string) $user->password)) {
            throw ValidationException::withMessages(['password' => 'The provided password is incorrect.']);
        }

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return response()->noContent();
    }

    /**
     * Regenerate recovery codes
     */
    public function recoveryCodes(DisableTwoFactorRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->two_factor_confirmed_at === null) {
            abort(409, 'Two-factor authentication is not enabled.');
        }

        if (! Hash::check((string) $request->validated('password'), (string) $user->password)) {
            throw ValidationException::withMessages(['password' => 'The provided password is incorrect.']);
        }

        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();

        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->save();

        return response()->json(['recovery_codes' => $recoveryCodes]);
    }
}
