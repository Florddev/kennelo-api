<?php

declare(strict_types=1);

namespace Tests\Support;

use Stripe\Checkout\Session;
use Stripe\Collection;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\Price;
use Stripe\Product;
use Stripe\Refund;
use Stripe\SearchResult;
use Stripe\Service\Checkout\SessionService;
use Stripe\Service\CustomerService;
use Stripe\Service\InvoiceService;
use Stripe\Service\PaymentIntentService;
use Stripe\Service\PriceService;
use Stripe\Service\ProductService;
use Stripe\Service\RefundService;
use Stripe\Service\SubscriptionService;
use Stripe\Service\TransferService;
use Stripe\StripeClient;
use Stripe\Subscription;
use Stripe\Transfer;

class FakeStripeClient extends StripeClient
{
    /** @var array<int, array{method: string, params: mixed}> */
    public array $calls = [];

    private CustomerService $customerService;

    private PaymentIntentService $paymentIntentService;

    private TransferService $transferService;

    private RefundService $refundService;

    private SubscriptionService $subscriptionService;

    private InvoiceService $invoiceService;

    private ProductService $productService;

    private PriceService $priceService;

    private object $checkoutService;

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

        $this->subscriptionService = new class($this, $client) extends SubscriptionService
        {
            public function __construct($parent, private FakeStripeClient $recorder)
            {
                parent::__construct($parent);
            }

            public function update($id, $params = null, $opts = null): Subscription
            {
                $this->recorder->record('subscriptions.update', ['id' => $id, 'params' => $params]);

                return Subscription::constructFrom([
                    'id' => $id,
                    'status' => 'active',
                    'cancel_at_period_end' => true,
                ]);
            }
        };

        $this->invoiceService = new class($this, $client) extends InvoiceService
        {
            public function __construct($parent, private FakeStripeClient $recorder)
            {
                parent::__construct($parent);
            }

            public function all($params = null, $opts = null): Collection
            {
                $this->recorder->record('invoices.all', $params);

                return Collection::constructFrom(['data' => []]);
            }
        };

        $this->productService = new class($this, $client) extends ProductService
        {
            public function __construct($parent, private FakeStripeClient $recorder)
            {
                parent::__construct($parent);
            }

            public function search($params = null, $opts = null): SearchResult
            {
                $this->recorder->record('products.search', $params);

                return SearchResult::constructFrom(['data' => []]);
            }

            public function create($params = null, $opts = null): Product
            {
                $this->recorder->record('products.create', $params);

                return Product::constructFrom(['id' => 'prod_test_'.uniqid()]);
            }
        };

        $this->priceService = new class($this, $client) extends PriceService
        {
            public function __construct($parent, private FakeStripeClient $recorder)
            {
                parent::__construct($parent);
            }

            public function search($params = null, $opts = null): SearchResult
            {
                $this->recorder->record('prices.search', $params);

                return SearchResult::constructFrom(['data' => []]);
            }

            public function create($params = null, $opts = null): Price
            {
                $this->recorder->record('prices.create', $params);

                return Price::constructFrom(['id' => 'price_test_'.uniqid()]);
            }
        };

        $sessionService = new class($this, $client) extends SessionService
        {
            public function __construct($parent, private FakeStripeClient $recorder)
            {
                parent::__construct($parent);
            }

            public function create($params = null, $opts = null): Session
            {
                $this->recorder->record('checkout.sessions.create', $params);

                return Session::constructFrom([
                    'id' => 'cs_test_'.uniqid(),
                    'url' => 'https://checkout.stripe.test/session/'.uniqid(),
                ]);
            }
        };

        $this->checkoutService = new class($sessionService)
        {
            public function __construct(public SessionService $sessions) {}
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
            'subscriptions' => $this->subscriptionService,
            'invoices' => $this->invoiceService,
            'products' => $this->productService,
            'prices' => $this->priceService,
            'checkout' => $this->checkoutService,
            default => parent::__get($name),
        };
    }
}
