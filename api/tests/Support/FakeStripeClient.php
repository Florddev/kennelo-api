<?php

declare(strict_types=1);

namespace Tests\Support;

use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Service\CustomerService;
use Stripe\Service\PaymentIntentService;
use Stripe\Service\RefundService;
use Stripe\Service\TransferService;
use Stripe\StripeClient;
use Stripe\Transfer;

class FakeStripeClient extends StripeClient
{
    private CustomerService $customerService;

    private PaymentIntentService $paymentIntentService;

    private TransferService $transferService;

    private RefundService $refundService;

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
                    'latest_charge' => null,
                    'status' => 'requires_capture',
                    'client_secret' => 'pi_test_secret_'.uniqid(),
                ]);
            }

            public function capture($id, $params = null, $opts = null): PaymentIntent
            {
                return PaymentIntent::constructFrom([
                    'id' => $id,
                    'latest_charge' => 'ch_test_'.uniqid(),
                    'status' => 'succeeded',
                ]);
            }

            public function cancel($id, $params = null, $opts = null): PaymentIntent
            {
                return PaymentIntent::constructFrom([
                    'id' => $id,
                    'status' => 'canceled',
                ]);
            }
        };

        $this->transferService = new class($this) extends TransferService
        {
            public function create($params = null, $opts = null): Transfer
            {
                return Transfer::constructFrom(['id' => 'tr_test_'.uniqid()]);
            }
        };

        $this->refundService = new class($this) extends RefundService
        {
            public function create($params = null, $opts = null): Refund
            {
                return Refund::constructFrom(['id' => 're_test_'.uniqid(), 'amount' => 1000]);
            }
        };
    }

    public function __get($name)
    {
        return match ($name) {
            'customers' => $this->customerService,
            'paymentIntents' => $this->paymentIntentService,
            'transfers' => $this->transferService,
            'refunds' => $this->refundService,
            default => parent::__get($name),
        };
    }
}
