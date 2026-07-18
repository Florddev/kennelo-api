<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use App\Models\User;
use Stripe\StripeClient;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class StripePaymentMethodService
{
    public function __construct(
        private StripeClient $stripe,
        private StripeCustomerService $customerService,
    ) {}

    public function createSetupIntent(User $user): array
    {
        $customerId = $this->customerService->getOrCreateCustomer($user);

        $intent = $this->stripe->setupIntents->create([
            'customer' => $customerId,
            'usage' => 'off_session',
            'payment_method_types' => ['card'],
        ]);

        return [
            'client_secret' => $intent->client_secret,
            'setup_intent_id' => $intent->id,
        ];
    }

    public function createSetupCheckoutSession(User $user): array
    {
        $customerId = $this->customerService->getOrCreateCustomer($user);

        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'setup',
            'currency' => (string) config('services.stripe.currency', 'eur'),
            'customer' => $customerId,
            'payment_method_types' => ['card'],
            'ui_mode' => 'embedded_page',
            'redirect_on_completion' => 'never',
        ]);

        return [
            'client_secret' => $session->client_secret,
            'session_id' => $session->id,
        ];
    }

    public function list(User $user): array
    {
        if ($user->stripe_customer_id === null) {
            return [];
        }

        $pms = $this->stripe->paymentMethods->all([
            'customer' => $user->stripe_customer_id,
            'type' => 'card',
            'limit' => 100,
        ]);

        $customer = $this->stripe->customers->retrieve($user->stripe_customer_id);
        $defaultId = $customer->invoice_settings->default_payment_method ?? null;

        if ($defaultId === null && count($pms->data) > 0) {
            $defaultId = $pms->data[0]->id;
            $this->stripe->customers->update($user->stripe_customer_id, [
                'invoice_settings' => ['default_payment_method' => $defaultId],
            ]);
        }

        $result = [];
        foreach ($pms->data as $pm) {
            $result[] = [
                'id' => $pm->id,
                'brand' => $pm->card->brand,
                'last4' => $pm->card->last4,
                'exp_month' => $pm->card->exp_month,
                'exp_year' => $pm->card->exp_year,
                'is_default' => ($pm->id === $defaultId),
                'cardholder_name' => $pm->billing_details->name ?? null,
            ];
        }

        return $result;
    }

    public function setDefault(User $user, string $paymentMethodId): void
    {
        $customerId = $this->customerService->getOrCreateCustomer($user);

        $this->assertOwnership($user, $paymentMethodId);

        $this->stripe->customers->update($customerId, [
            'invoice_settings' => ['default_payment_method' => $paymentMethodId],
        ]);
    }

    public function delete(User $user, string $paymentMethodId): void
    {
        $this->assertOwnership($user, $paymentMethodId);

        $this->stripe->paymentMethods->detach($paymentMethodId);
    }

    private function assertOwnership(User $user, string $paymentMethodId): void
    {
        $pm = $this->stripe->paymentMethods->retrieve($paymentMethodId);

        throw_if(($pm->customer ?? null) !== $user->stripe_customer_id, AccessDeniedHttpException::class, 'Payment method does not belong to the current user.');
    }
}
