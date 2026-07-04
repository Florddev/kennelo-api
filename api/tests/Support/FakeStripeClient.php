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
    /** @var array<int, array{method: string, params: mixed}> */
    public array $calls = [];

    private CustomerService $customerService;

    private PaymentIntentService $paymentIntentService;

    private TransferService $transferService;

    private RefundService $refundService;

    public function __construct()
    {
        parent::__construct(['api_key' => 'sk_test_fake']);

        $client = $this;

        $this->customerService = new class($this, $client) extends CustomerService
        {
            public function __construct($parent, private FakeStripeClient $recorder)
            {
                parent::__construct($parent);
            }

            public function create($params = null, $opts = null): Customer
            {
                $this->recorder->record('customers.create', $params);

                return Customer::constructFrom(['id' => 'cus_test_'.uniqid()]);
            }
        };

        $this->paymentIntentService = new class($this, $client) extends PaymentIntentService
        {
            public function __construct($parent, private FakeStripeClient $recorder)
            {
                parent::__construct($parent);
            }

            public function create($params = null, $opts = null): PaymentIntent
            {
                $this->recorder->record('paymentIntents.create', $params);

                return PaymentIntent::constructFrom([
                    'id' => 'pi_test_'.uniqid(),
                    'latest_charge' => null,
                    'status' => 'requires_capture',
                    'client_secret' => 'pi_test_secret_'.uniqid(),
                ]);
            }

            public function capture($id, $params = null, $opts = null): PaymentIntent
            {
                $this->recorder->record('paymentIntents.capture', $id);

                return PaymentIntent::constructFrom([
                    'id' => $id,
                    'latest_charge' => 'ch_test_'.uniqid(),
                    'status' => 'succeeded',
                ]);
            }

            public function cancel($id, $params = null, $opts = null): PaymentIntent
            {
                $this->recorder->record('paymentIntents.cancel', $id);

                return PaymentIntent::constructFrom([
                    'id' => $id,
                    'status' => 'canceled',
                ]);
            }
        };

        $this->transferService = new class($this, $client) extends TransferService
        {
            public function __construct($parent, private FakeStripeClient $recorder)
            {
                parent::__construct($parent);
            }

            public function create($params = null, $opts = null): Transfer
            {
                $this->recorder->record('transfers.create', $params);

                return Transfer::constructFrom(['id' => 'tr_test_'.uniqid()]);
            }
        };

        $this->refundService = new class($this, $client) extends RefundService
        {
            public function __construct($parent, private FakeStripeClient $recorder)
            {
                parent::__construct($parent);
            }

            public function create($params = null, $opts = null): Refund
            {
                $this->recorder->record('refunds.create', $params);

                return Refund::constructFrom(['id' => 're_test_'.uniqid(), 'amount' => 1000]);
            }
        };
    }

    public function record(string $method, mixed $params): void
    {
        $this->calls[] = ['method' => $method, 'params' => $params];
    }

    public function called(string $method): bool
    {
        foreach ($this->calls as $call) {
            if ($call['method'] === $method) {
                return true;
            }
        }

        return false;
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
