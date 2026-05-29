<?php

declare(strict_types=1);

namespace App\Http\Controllers\Establishment;

use App\Enums\ApiStatus;
use App\Http\Controllers\Controller;
use App\Models\Establishment;
use App\Services\Establishment\StripeConnectService;
use Illuminate\Http\JsonResponse;

class StripeConnectController extends Controller
{
    public function __construct(
        private StripeConnectService $stripeConnectService
    ) {}

    public function onboardingLink(Establishment $establishment): JsonResponse
    {
        $this->authorize('managePayments', $establishment);

        $url = $this->stripeConnectService->getOrCreateOnboardingLink($establishment);

        return response()->json([
            'data' => ['url' => $url],
            'status' => ApiStatus::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }

    public function status(Establishment $establishment): JsonResponse
    {
        $this->authorize('view', $establishment);

        $establishment = $this->stripeConnectService->syncAndGetStatus($establishment);

        return response()->json([
            'data' => [
                'stripe_account_id' => $establishment->stripe_account_id,
                'onboarding_completed' => (bool) $establishment->stripe_onboarding_completed,
                'charges_enabled' => (bool) $establishment->stripe_charges_enabled,
                'payouts_enabled' => (bool) $establishment->stripe_payouts_enabled,
            ],
            'status' => ApiStatus::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }
}
