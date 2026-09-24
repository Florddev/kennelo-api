<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RenewPasswordRequest;
use App\Services\AuthenticationService;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;

/**
 * @tags Auth
 */
class PasswordRenewalController extends Controller
{
    public function __construct(
        private readonly AuthenticationService $authentication,
        private readonly UserService $userService,
    ) {}

    /**
     * Renew an expired password
     *
     * @unauthenticated
     */
    public function store(RenewPasswordRequest $request): JsonResponse
    {
        $pending = $this->authentication->pendingPasswordRenewal($request);

        abort_if($pending === null, 401, __('login.no_pending_password_renewal'));

        $this->userService->renewExpiredPassword($pending['user'], (string) $request->validated('password'));

        return $this->authentication->login($request, $pending['user'], $pending['remember']);
    }
}
