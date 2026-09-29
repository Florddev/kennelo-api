<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\TwoFactorChallengeRequest;
use App\Services\AuthenticationService;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * @tags Auth
 */
class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $authentication,
        private readonly TwoFactorService $twoFactorService,
    ) {}

    /**
     * Login
     *
     * @unauthenticated
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $user = $request->authenticate();

        return $this->authentication->proceed($request, $user, $request->boolean('remember'));
    }

    /**
     * Two-factor challenge
     *
     * @unauthenticated
     */
    public function twoFactorChallenge(TwoFactorChallengeRequest $request): JsonResponse
    {
        $pending = $this->authentication->pendingTwoFactor($request);

        abort_if($pending === null, 401, __('login.no_pending_two_factor'));

        $user = $pending['user'];

        if (! $this->twoFactorService->attemptChallenge($user, $request->validated('code'), $request->validated('recovery_code'))) {
            if ($this->twoFactorService->challengeIsLocked($user)) {
                $this->authentication->forgetPending($request);
            }

            throw ValidationException::withMessages(['code' => __('two_factor.invalid_code')]);
        }

        $rememberToken = $request->boolean('remember')
            ? $this->twoFactorService->rememberDevice($user)
            : null;

        $response = $this->authentication->afterTwoFactor($request, $user, $pending['remember']);

        if ($rememberToken !== null) {
            $response->setData([...$response->getData(true), 'remember_token' => $rememberToken]);
        }

        return $response;
    }

    /**
     * Logout
     */
    public function destroy(Request $request): Response
    {
        $this->authentication->logout($request);

        return response()->noContent();
    }
}
