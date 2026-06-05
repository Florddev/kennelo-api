<?php

declare(strict_types=1);

namespace Tests\Support;

use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\Service\CustomerService;
use Stripe\Service\PaymentIntentService;
use Stripe\StripeClient;

class FakeStripeClient extends StripeClient
{
    private CustomerService $customerService;

    private PaymentIntentService $paymentIntentService;

    public function __construct()
    {
        parent::__construct(['api_key' => 'sk_test_fake']);

        $this->customerService = new class($this) extends CustomerService
        {
            public function create($params = null, $opts = null): Customer
            {
                return Customer::constructFrom(['id' => 'cus_test_'.uniqid()]);
            }
        };

        $this->paymentIntentService = new class($this) extends PaymentIntentService
        {
            public function create($params = null, $opts = null): PaymentIntent
            {
                return PaymentIntent::constructFrom([
                    'id' => 'pi_test_'.uniqid(),
                    'latest_charge' => 'ch_test_'.uniqid(),
                    'status' => 'succeeded',
                    'client_secret' => 'pi_test_secret_'.uniqid(),
                ]);
            }
        };
    }

    public function __get($name)
    {
        return match ($name) {
            'customers' => $this->customerService,
            'paymentIntents' => $this->paymentIntentService,
            default => parent::__get($name),
        };
    }
}
