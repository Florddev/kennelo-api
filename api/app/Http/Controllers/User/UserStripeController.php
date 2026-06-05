<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Enums\ApiStatus;
use App\Http\Controllers\Controller;
use App\Services\Establishment\StripeConnectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserStripeController extends Controller
{
    public function __construct(private StripeConnectService $service) {}

    public function accountSession(Request $request): JsonResponse
    {
        $data = $this->service->createAccountSession($request->user());

        return response()->json([
            'data' => $data,
            'status' => ApiStatus::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $user = $this->service->syncUserAccount($request->user());

        return response()->json([
            'data' => [
                'stripe_account_id' => $user->stripe_account_id,
                'onboarding_completed' => (bool) $user->stripe_onboarding_completed,
                'charges_enabled' => (bool) $user->stripe_charges_enabled,
                'payouts_enabled' => (bool) $user->stripe_payouts_enabled,
            ],
            'status' => ApiStatus::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }
}
