<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use App\Enums\SubscriptionPaymentStatusEnum;
use App\Models\StripeEvent;
use App\Services\Booking\BookingDisputeService;
use App\Services\Booking\BookingPayoutService;
use App\Services\Booking\BookingWebhookService;
use App\Services\Subscription\SubscriptionWebhookService;
use Stripe\Account;
use Stripe\Dispute;
use Stripe\Event;
use Stripe\Invoice;
use Stripe\PaymentIntent;
use Stripe\Subscription;
use Stripe\Transfer;
use Throwable;

/**
 * Traite chaque événement Stripe une seule fois, même si Stripe le livre plusieurs fois : comptes Connect,
 * abonnements et leurs factures, paiements de réservation et versements.
 */
class StripeWebhookService
{
    public function __construct(
        private readonly StripeConnectService $connect,
        private readonly SubscriptionWebhookService $subscriptions,
        private readonly BookingWebhookService $bookings,
        private readonly BookingPayoutService $payouts,
        private readonly BookingDisputeService $disputes,
    ) {}

    public function handle(Event $event): void
    {
        StripeEvent::firstOrCreate(['id' => $event->id], ['type' => $event->type]);

        // Réservation atomique : une seule livraison passe, les autres trouvent l'événement déjà pris.
        $claimed = StripeEvent::whereKey($event->id)->whereNull('processed_at')->update(['processed_at' => now()]);

        if ($claimed === 0) {
            return;
        }

        try {
            $this->dispatch($event);
        } catch (Throwable $exception) {
            // Libéré pour que la nouvelle tentative de Stripe puisse le traiter.
            StripeEvent::whereKey($event->id)->update(['processed_at' => null]);

            throw $exception;
        }
    }

    private function dispatch(Event $event): void
    {
        $object = $event->data->object;

        match (true) {
            $event->type === 'account.updated' && $object instanceof Account => $this->connect->sync($object),
            str_starts_with($event->type, 'customer.subscription.') && $object instanceof Subscription => $this->subscriptions->syncSubscription($object),
            $event->type === 'invoice.paid' && $object instanceof Invoice => $this->subscriptions->recordInvoice($object, SubscriptionPaymentStatusEnum::PAID),
            $event->type === 'invoice.payment_failed' && $object instanceof Invoice => $this->subscriptions->recordInvoice($object, SubscriptionPaymentStatusEnum::FAILED),
            in_array($event->type, BookingWebhookService::PAYMENT_EVENTS, true) && $object instanceof PaymentIntent => $this->bookings->syncPaymentIntent($object),
            $event->type === 'transfer.reversed' && $object instanceof Transfer => $this->payouts->reverse($object),
            in_array($event->type, BookingDisputeService::EVENTS, true) && $object instanceof Dispute => $this->disputes->sync($object),
            default => null,
        };
    }
}
