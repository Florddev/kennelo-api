<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Models\Activity;
use App\Models\User;
use Stripe\StripeClient;

class StripeConnectService
{
    public function __construct(
        private StripeClient $stripe
    ) {}

    public function getOrCreateOnboardingLink(Activity $activity): string
    {
        $activity->loadMissing('manager');
        $manager = $activity->manager;

        if (! $manager->stripe_account_id) {
            $account = $this->callStripe(fn () => $this->stripe->accounts->create([
                'type' => 'express',
                'country' => 'FR',
                'email' => $manager->email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'business_profile' => array_filter([
                    'name' => $activity->name,
                    'url' => $activity->website,
                ]),
                'metadata' => [
                    'manager_id' => $manager->id,
                ],
            ]));

            $manager->update(['stripe_account_id' => $account->id]);

            if ($activity->stripe_account_id) {
                $activity->update(['stripe_account_id' => $account->id]);
            }
        }

        $accountId = $activity->resolveStripeAccountId();
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        $accountLink = $this->callStripe(fn () => $this->stripe->accountLinks->create([
            'account' => $accountId,
            'return_url' => $frontendUrl."/hosting/host/{$activity->id}",
            'refresh_url' => $frontendUrl."/hosting/host/{$activity->id}",
            'type' => 'account_onboarding',
        ]));

        return $accountLink->url;
    }

    public function syncAndGetStatus(Activity $activity): Activity
    {
        $activity->loadMissing('manager');
        $accountId = $activity->resolveStripeAccountId();

        if ($accountId === null) {
            return $activity;
        }

        $needsRefresh = ! $activity->resolveChargesEnabled();

        if ($needsRefresh) {
            $account = $this->callStripe(fn () => $this->stripe->accounts->retrieve($accountId));

            $manager = $activity->manager;
            if ($manager !== null && $manager->stripe_account_id === $accountId) {
                $manager->update([
                    'stripe_charges_enabled' => $account->charges_enabled,
                    'stripe_payouts_enabled' => $account->payouts_enabled,
                    'stripe_onboarding_completed' => $account->details_submitted,
                ]);
            }

            if ($activity->stripe_account_id !== null) {
                $activity->update([
                    'stripe_charges_enabled' => $account->charges_enabled,
                    'stripe_payouts_enabled' => $account->payouts_enabled,
                    'stripe_onboarding_completed' => $account->details_submitted,
                ]);
            }

            $activity->refresh();
        }

        return $activity;
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

    public function createAccountSession(User $user): array
    {
        if (! $user->stripe_account_id) {
            $account = $this->callStripe(fn () => $this->stripe->accounts->create([
                'type' => 'express',
                'country' => 'FR',
                'email' => $user->email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'metadata' => [
                    'manager_id' => $user->id,
                ],
            ]));

            $user->forceFill(['stripe_account_id' => $account->id])->save();
        }

        $session = $this->callStripe(fn () => $this->stripe->accountSessions->create([
            'account' => $user->stripe_account_id,
            'components' => [
                'account_onboarding' => ['enabled' => true],
            ],
        ]));

        return ['client_secret' => $session->client_secret];
    }

    public function syncUserAccount(User $user): User
    {
        if (! $user->stripe_account_id) {
            return $user;
        }

        $account = $this->callStripe(fn () => $this->stripe->accounts->retrieve($user->stripe_account_id));

        $user->update([
            'stripe_charges_enabled' => (bool) $account->charges_enabled,
            'stripe_payouts_enabled' => (bool) $account->payouts_enabled,
            'stripe_onboarding_completed' => (bool) $account->details_submitted,
        ]);

        return $user->fresh();
    }
}
