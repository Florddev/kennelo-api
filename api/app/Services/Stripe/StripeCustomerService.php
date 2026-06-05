<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use App\Models\User;
use Stripe\StripeClient;

class StripeCustomerService
{
    public function __construct(private readonly StripeClient $stripe) {}

    public function getOrCreateCustomer(User $user): string
    {
        if (! empty($user->stripe_customer_id)) {
            return (string) $user->stripe_customer_id;
        }

        $name = trim($user->first_name.' '.$user->last_name) ?: $user->email;

        $customer = $this->stripe->customers->create([
            'email' => $user->email,
            'name' => $name,
            'metadata' => ['user_id' => $user->id],
        ]);

        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer->id;
    }
}
