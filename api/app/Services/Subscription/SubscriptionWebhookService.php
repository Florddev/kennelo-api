<?php

declare(strict_types=1);

namespace App\Services\Subscription;

use App\Enums\FinancialOperationTypeEnum;
use App\Enums\NotificationTypeEnum;
use App\Enums\SubscriptionPaymentStatusEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Services\Finance\FinancialJournalService;
use App\Services\Notification\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\Invoice as StripeInvoice;
use Stripe\Subscription as StripeSubscription;

class SubscriptionWebhookService
{
    public function __construct(
        private readonly FinancialJournalService $journal,
        private readonly NotificationService $notifications,
        private readonly SubscriptionDowngradeService $downgrade,
    ) {}

    public function handle(Event $event): void
    {
        match ($event->type) {
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted' => $this->onSubscriptionChanged($event),
            'invoice.paid' => $this->onInvoicePaid($event),
            'invoice.payment_failed' => $this->onInvoicePaymentFailed($event),
            default => null,
        };
    }

    private function onSubscriptionChanged(Event $event): void
    {
        $object = $event->data->object ?? null;

        if (! $object instanceof StripeSubscription) {
            return;
        }

        DB::transaction(function () use ($object): void {
            $subscription = Subscription::where('stripe_subscription_id', $object->id)
                ->lockForUpdate()
                ->first();

            $activityId = $object->metadata->activity_id ?? null;
            $planSlug = $object->metadata->plan_slug ?? null;

            if ($subscription === null) {
                if ($activityId === null) {
                    Log::warning('Stripe webhook: subscription event without activity metadata', [
                        'stripe_subscription_id' => $object->id,
                    ]);

                    return;
                }

                $subscription = new Subscription([
                    'activity_id' => $activityId,
                    'stripe_subscription_id' => $object->id,
                ]);
            }

            $plan = $this->resolvePlan($planSlug, $object->items->data[0]->price->id ?? null);
            $planId = $plan instanceof SubscriptionPlan ? $plan->id : $subscription->subscription_plan_id;

            $wasEffective = $subscription->exists && $subscription->isEffective();

            $subscription->fill([
                'subscription_plan_id' => $planId,
                'stripe_customer_id' => $object->customer ?? $subscription->stripe_customer_id,
                'status' => SubscriptionStatusEnum::fromStripe((string) $object->status),
                'current_period_start' => $this->toDate($object->current_period_start ?? null),
                'current_period_end' => $this->toDate($object->current_period_end ?? null),
                'trial_ends_at' => $this->toDate($object->trial_end ?? null),
                'canceled_at' => $this->toDate($object->canceled_at ?? null),
                'ends_at' => ($object->cancel_at_period_end ?? false)
                    ? $this->toDate($object->current_period_end ?? null)
                    : $subscription->ends_at,
            ]);

            $subscription->save();

            $isEffective = $subscription->isEffective();

            if ($wasEffective && ! $isEffective) {
                $subscription->loadMissing('activity.manager');

                if ($subscription->activity !== null) {
                    $this->downgrade->apply($subscription->activity);
                }
            }

            if (! $wasEffective && $isEffective) {
                $subscription->loadMissing('activity.manager');
                $manager = $subscription->activity?->manager;

                if ($manager !== null) {
                    $this->notifications->notify(
                        $manager,
                        NotificationTypeEnum::SUBSCRIPTION_ACTIVATED,
                        ['activity_id' => $subscription->activity_id],
                    );
                }
            }
        });
    }

    private function onInvoicePaid(Event $event): void
    {
        $invoice = $event->data->object ?? null;

        if (! $invoice instanceof StripeInvoice) {
            return;
        }

        $stripeSubscriptionId = $invoice->subscription ?? null;

        if ($stripeSubscriptionId === null) {
            return;
        }

        DB::transaction(function () use ($invoice, $stripeSubscriptionId): void {
            $subscription = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)
                ->lockForUpdate()
                ->first();

            if ($subscription === null) {
                return;
            }

            $amount = bcdiv((string) ($invoice->amount_paid ?? 0), '100', 2);

            SubscriptionPayment::updateOrCreate(
                ['stripe_invoice_id' => $invoice->id],
                [
                    'subscription_id' => $subscription->id,
                    'stripe_payment_intent_id' => $invoice->payment_intent ?? null,
                    'amount' => $amount,
                    'currency' => strtoupper((string) ($invoice->currency ?? config('services.stripe.currency', 'eur'))),
                    'status' => SubscriptionPaymentStatusEnum::PAID,
                    'paid_at' => Carbon::now(),
                    'invoice_pdf_url' => $invoice->invoice_pdf ?? null,
                ]
            );

            $this->journal->record(
                FinancialOperationTypeEnum::SUBSCRIPTION_PAYMENT,
                null,
                $amount,
                $invoice->id,
                ['subscription_id' => $subscription->id, 'source' => 'webhook'],
            );
        });
    }

    private function onInvoicePaymentFailed(Event $event): void
    {
        $invoice = $event->data->object ?? null;

        if (! $invoice instanceof StripeInvoice) {
            return;
        }

        $stripeSubscriptionId = $invoice->subscription ?? null;

        if ($stripeSubscriptionId === null) {
            return;
        }

        DB::transaction(function () use ($invoice, $stripeSubscriptionId): void {
            $subscription = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)
                ->lockForUpdate()
                ->first();

            if ($subscription === null) {
                return;
            }

            SubscriptionPayment::updateOrCreate(
                ['stripe_invoice_id' => $invoice->id],
                [
                    'subscription_id' => $subscription->id,
                    'stripe_payment_intent_id' => $invoice->payment_intent ?? null,
                    'amount' => bcdiv((string) ($invoice->amount_due ?? 0), '100', 2),
                    'currency' => strtoupper((string) ($invoice->currency ?? config('services.stripe.currency', 'eur'))),
                    'status' => SubscriptionPaymentStatusEnum::FAILED,
                    'paid_at' => null,
                    'invoice_pdf_url' => $invoice->invoice_pdf ?? null,
                ]
            );

            $subscription->loadMissing('activity.manager');
            $manager = $subscription->activity?->manager;

            if ($manager !== null) {
                $this->notifications->notify(
                    $manager,
                    NotificationTypeEnum::SUBSCRIPTION_PAYMENT_FAILED,
                    ['activity_id' => $subscription->activity_id],
                );
            }
        });
    }

    private function resolvePlan(?string $slug, ?string $stripePriceId): ?SubscriptionPlan
    {
        if ($slug !== null) {
            $plan = SubscriptionPlan::where('slug', $slug)->first();

            if ($plan !== null) {
                return $plan;
            }
        }

        if ($stripePriceId !== null) {
            return SubscriptionPlan::where('stripe_price_id', $stripePriceId)->first();
        }

        return null;
    }

    private function toDate(?int $timestamp): ?Carbon
    {
        return $timestamp === null ? null : Carbon::createFromTimestamp($timestamp);
    }
}
