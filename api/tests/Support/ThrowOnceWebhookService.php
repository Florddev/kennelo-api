<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Services\Stripe\StripeWebhookService;
use RuntimeException;
use Stripe\Event;

class ThrowOnceWebhookService extends StripeWebhookService
{
    public int $calls = 0;

    public function handleEvent(Event $event): void
    {
        $this->calls++;

        if ($this->calls === 1) {
            throw new RuntimeException('boom');
        }
    }
}
