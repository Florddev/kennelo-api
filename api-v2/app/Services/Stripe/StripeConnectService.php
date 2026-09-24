<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use App\Enums\NotificationTypeEnum;
use App\Models\Organization;
use App\Services\Notification\NotificationService;
use Stripe\Account;
use Stripe\StripeClient;

/**
 * Compte Stripe Connect de l'entreprise : il reçoit les versements des réservations.
 * L'inscription se fait dans le front avec les composants intégrés de Stripe.
 */
class StripeConnectService
{
    public function __construct(
        private readonly StripeClient $stripe,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Crée le compte au premier appel, puis ouvre une session pour le composant d'inscription.
     */
    public function createAccountSession(Organization $organization): string
    {
        if (blank($organization->stripe_account_id)) {
            $account = $this->stripe->accounts->create([
                'type' => 'express',
                'country' => 'FR',
                'email' => $organization->owner?->email,
                'business_profile' => ['name' => $organization->legal_name],
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'metadata' => ['organization_id' => $organization->id],
            ]);

            $organization->forceFill(['stripe_account_id' => $account->id])->save();
        }

        $session = $this->stripe->accountSessions->create([
            'account' => $organization->stripe_account_id,
            'components' => [
                'account_onboarding' => ['enabled' => true],
            ],
        ]);

        return $session->client_secret;
    }

    /**
     * Relit l'état du compte chez Stripe, sans attendre le webhook.
     */
    public function refresh(Organization $organization): Organization
    {
        if (filled($organization->stripe_account_id)) {
            $this->apply($organization, $this->stripe->accounts->retrieve($organization->stripe_account_id));
        }

        return $organization;
    }

    /**
     * Reçu par le webhook account.updated.
     */
    public function sync(Account $account): void
    {
        $organization = Organization::where('stripe_account_id', $account->id)->first();

        if ($organization !== null) {
            $this->apply($organization, $account);
        }
    }

    private function apply(Organization $organization, Account $account): void
    {
        $wasOnboarded = $organization->stripe_onboarding_completed;

        $organization->forceFill([
            'stripe_charges_enabled' => (bool) $account->charges_enabled,
            'stripe_payouts_enabled' => (bool) $account->payouts_enabled,
            'stripe_onboarding_completed' => (bool) $account->details_submitted,
        ])->save();

        if (! $wasOnboarded && $organization->stripe_onboarding_completed && $organization->owner !== null) {
            $this->notifications->notify($organization->owner, NotificationTypeEnum::STRIPE_ACCOUNT_ACTIVATED, [
                'organization_id' => $organization->id,
                'organization_name' => $organization->legal_name,
            ]);
        }
    }
}
