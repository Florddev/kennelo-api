<?php

declare(strict_types=1);

namespace Tests\Support;

use Stripe\Exception\InvalidRequestException;
use Stripe\PaymentIntent;
use Stripe\Service\PaymentIntentService;

class FailingPaymentStripeClient extends FakeStripeClient
{
    private PaymentIntentService $failingPaymentIntentService;

    public function __construct()
    {
        parent::__construct();

        $this->failingPaymentIntentService = new class($this) extends PaymentIntentService
        {
            public function create($params = null, $opts = null): PaymentIntent
            {
                throw new InvalidRequestException('Your card was declined.');
            }
        };
    }

    public function __get($name)
    {
        if ($name === 'paymentIntents') {
            return $this->failingPaymentIntentService;
        }

        return parent::__get($name);
    }
}
