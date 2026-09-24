<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use App\Models\Organization;
use App\Models\User;
use Stripe\StripeClient;

/**
 * Clients Stripe : le user paie ses réservations, l'entreprise paie son abonnement.
 */
class StripeCustomerService
{
    public function __construct(private readonly StripeClient $stripe) {}

    public function getOrCreateCustomer(User $user): string
    {
        if (filled($user->stripe_customer_id)) {
            return (string) $user->stripe_customer_id;
        }

        $name = trim($user->first_name.' '.$user->last_name) ?: $user->email;

        $customer = $this->stripe->customers->create([
            'email' => $user->email,
            'name' => $name,
            'metadata' => ['user_id' => $user->id],
        ]);

        $user->forceFill(['stripe_customer_id' => $customer->id])->save();

        return $customer->id;
    }

    public function getOrCreateOrganizationCustomer(Organization $organization): string
    {
        if (filled($organization->stripe_customer_id)) {
            return (string) $organization->stripe_customer_id;
        }

        $customer = $this->stripe->customers->create([
            'email' => $organization->owner?->email,
            'name' => $organization->legal_name,
            'metadata' => ['organization_id' => $organization->id],
        ]);

        $organization->forceFill(['stripe_customer_id' => $customer->id])->save();

        return $customer->id;
    }
}
