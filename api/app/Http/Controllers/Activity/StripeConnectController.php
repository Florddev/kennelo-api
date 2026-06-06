<?php

declare(strict_types=1);

namespace App\Http\Controllers\Activity;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Services\Activity\StripeConnectService;
use Illuminate\Http\JsonResponse;

class StripeConnectController extends Controller
{
    public function __construct(
        private StripeConnectService $stripeConnectService
    ) {}

    public function onboardingLink(Activity $activity): JsonResponse
    {
        $this->authorize('managePayments', $activity);

        $url = $this->stripeConnectService->getOrCreateOnboardingLink($activity);

        return response()->json([
            'data' => ['url' => $url],
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }

    public function status(Activity $activity): JsonResponse
    {
        $this->authorize('view', $activity);

        $activity = $this->stripeConnectService->syncAndGetStatus($activity);

        return response()->json([
            'data' => [
                'stripe_account_id' => $activity->stripe_account_id,
                'onboarding_completed' => (bool) $activity->stripe_onboarding_completed,
                'charges_enabled' => (bool) $activity->stripe_charges_enabled,
                'payouts_enabled' => (bool) $activity->stripe_payouts_enabled,
            ],
            'status' => ApiStatusEnum::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }
}
