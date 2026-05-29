<?php

declare(strict_types=1);

namespace App\Services\Establishment;

use App\Models\Establishment;
use Stripe\StripeClient;

class StripeConnectService
{
    public function __construct(
        private StripeClient $stripe
    ) {}

    public function getOrCreateOnboardingLink(Establishment $establishment): string
    {
        if (! $establishment->stripe_account_id) {
            $establishment->loadMissing('manager');
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

        return $accountLink->url;
    }

    public function syncAndGetStatus(Establishment $establishment): Establishment
    {
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

        return $establishment;
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
