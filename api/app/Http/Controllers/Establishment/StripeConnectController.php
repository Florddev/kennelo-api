<?php

declare(strict_types=1);

namespace App\Http\Controllers\Establishment;

use App\Enums\ApiStatus;
use App\Http\Controllers\Controller;
use App\Models\Establishment;
use Illuminate\Http\JsonResponse;
use Stripe\StripeClient;

class StripeConnectController extends Controller
{
    public function __construct(
        private StripeClient $stripe
    ) {}

    public function onboardingLink(Establishment $establishment): JsonResponse
    {
        $this->authorize('update', $establishment);

        if (! $establishment->stripe_account_id) {
            $manager = $establishment->manager;

            $account = $this->callStripe(fn () => $this->stripe->accounts->create([
                'type' => 'express',
                'country' => 'FR',
                'email' => $establishment->email ?: $manager->email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'business_profile' => [
                    'name' => $establishment->name,
                    'url' => $establishment->website,
                ],
                'metadata' => [
                    'establishment_id' => $establishment->id,
                    'manager_id' => $manager->id,
                ],
            ]));

            $establishment->update(['stripe_account_id' => $account->id]);
        }

        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        $accountLink = $this->callStripe(fn () => $this->stripe->accountLinks->create([
            'account' => $establishment->stripe_account_id,
            'return_url' => $frontendUrl."/hosting/host/{$establishment->id}",
            'refresh_url' => $frontendUrl."/hosting/host/{$establishment->id}",
            'type' => 'account_onboarding',
        ]));

        return response()->json([
            'data' => ['url' => $accountLink->url],
            'status' => ApiStatus::SUCCESS,
            'timestamp' => human_date(now()),
        ]);
    }

    public function status(Establishment $establishment): JsonResponse
    {
        $this->authorize('view', $establishment);

        $needsRefresh = $establishment->stripe_account_id !== null
            && ! $establishment->stripe_charges_enabled;

        if ($needsRefresh) {
            $account = $this->callStripe(fn () => $this->stripe->accounts->retrieve($establishment->stripe_account_id));
            $establishment->update([
                'stripe_charges_enabled' => $account->charges_enabled,
                'stripe_payouts_enabled' => $account->payouts_enabled,
                'stripe_onboarding_completed' => $account->details_submitted,
            ]);
            $establishment->refresh();
        }

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

    private function callStripe(callable $fn): mixed
    {
        $previousLevel = error_reporting();
        error_reporting($previousLevel & ~E_USER_WARNING & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
        try {
            return $fn();
        } finally {
            error_reporting($previousLevel);
        }
    }
}
