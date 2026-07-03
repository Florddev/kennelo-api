<?php

declare(strict_types=1);

namespace App\Services\Admin\User;

use App\Enums\AdminActionTypeEnum;
use App\Models\User;
use App\Services\Admin\AdminActionService;
use App\Services\JWTService;

class ImpersonationService
{
    public function __construct(
        private JWTService $jwt,
        private AdminActionService $actions
    ) {}

    public function start(User $target, User $admin): array
    {
        $token = $this->jwt->generateImpersonationToken($target, $admin);

        $this->actions->log($admin, $target, AdminActionTypeEnum::IMPERSONATE_START);

        return [
            'token' => $token,
            'expires_in' => (int) config('jwt.impersonation_ttl', 15) * 60,
        ];
    }

    public function stop(string $token, User $impersonated, ?string $impersonatorId): void
    {
        $this->jwt->blacklistToken($token);

        $admin = $impersonatorId ? User::withInactive()->find($impersonatorId) : null;

        if ($admin) {
            $this->actions->log($admin, $impersonated, AdminActionTypeEnum::IMPERSONATE_STOP);
        }
    }
}
