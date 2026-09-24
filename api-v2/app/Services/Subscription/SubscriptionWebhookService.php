<?php

declare(strict_types=1);

namespace App\Services\Subscription;

use App\Enums\NotificationTypeEnum;
use App\Enums\SubscriptionPaymentStatusEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Invoice;
use Stripe\Subscription as StripeSubscription;

/**
 * Tient à jour l'abonnement et ses échéances à partir des événements Stripe.
 *
 * Depuis la version d'API « basil », les dates de période sont portées par les lignes de l'abonnement,
 * et une facture désigne son abonnement dans parent.subscription_details.
 */
class SubscriptionWebhookService
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function syncSubscription(StripeSubscription $object): void
    {
        DB::transaction(function () use ($object): void {
            $subscription = Subscription::where('stripe_subscription_id', $object->id)->lockForUpdate()->first()
                ?? $this->newSubscription($object);

            if ($subscription === null) {
                return;
            }

            $planId = $this->resolvePlan($object)->id ?? ($subscription->exists ? $subscription->subscription_plan_id : null);

            if ($planId === null) {
                Log::warning('Stripe webhook: subscription for an unknown plan', ['stripe_subscription_id' => $object->id]);

                return;
            }

            $wasEffective = $subscription->exists && $subscription->isEffective();
            $item = $object->items->data[0] ?? null;

            $subscription->fill([
                'subscription_plan_id' => $planId,
                'status' => SubscriptionStatusEnum::fromStripe((string) $object->status),
                'current_period_start' => $this->date($item->current_period_start ?? null),
                'current_period_end' => $this->date($item->current_period_end ?? null),
                'trial_ends_at' => $this->date($object->trial_end ?? null),
                'canceled_at' => $this->date($object->canceled_at ?? null),
                'ends_at' => $this->date($object->ended_at ?? null)
                    ?? (($object->cancel_at_period_end ?? false) ? $this->date($item->current_period_end ?? null) : null),
            ])->save();

            $isEffective = $subscription->isEffective();

            if ($wasEffective !== $isEffective) {
                $this->notifyOwner(
                    $subscription,
                    $isEffective ? NotificationTypeEnum::SUBSCRIPTION_ACTIVATED : NotificationTypeEnum::SUBSCRIPTION_DOWNGRADED,
                );
            }
        });
    }

    public function recordInvoice(Invoice $invoice, SubscriptionPaymentStatusEnum $status): void
    {
        $stripeSubscriptionId = $invoice->parent->subscription_details->subscription ?? null;

        if (! is_string($stripeSubscriptionId)) {
            return;
        }

        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();

        if ($subscription === null) {
            return;
        }

        $paid = $status === SubscriptionPaymentStatusEnum::PAID;

        SubscriptionPayment::updateOrCreate(
            ['stripe_invoice_id' => $invoice->id],
            [
                'subscription_id' => $subscription->id,
                'amount' => bcdiv((string) ($paid ? $invoice->amount_paid : $invoice->amount_due), '100', 2),
                'currency' => mb_strtoupper((string) ($invoice->currency ?? config('services.stripe.currency'))),
                'status' => $status,
                'paid_at' => $paid ? now() : null,
                'invoice_pdf_url' => $invoice->invoice_pdf ?? null,
            ],
        );

        if (! $paid) {
            $this->notifyOwner($subscription, NotificationTypeEnum::SUBSCRIPTION_PAYMENT_FAILED);
        }
    }

    private function newSubscription(StripeSubscription $object): ?Subscription
    {
        $organizationId = $object->metadata['organization_id'] ?? null;

        if (! is_string($organizationId) || ! Organization::withTrashed()->whereKey($organizationId)->exists()) {
            Log::warning('Stripe webhook: subscription without a known organization', [
                'stripe_subscription_id' => $object->id,
            ]);

            return null;
        }

        return new Subscription([
            'organization_id' => $organizationId,
            'stripe_subscription_id' => $object->id,
        ]);
    }

    private function resolvePlan(StripeSubscription $object): ?SubscriptionPlan
    {
        $slug = $object->metadata['plan_slug'] ?? null;
        $priceId = $object->items->data[0]->price->id ?? null;

        return SubscriptionPlan::query()
            ->when(is_string($slug), fn ($query) => $query->where('slug', $slug), fn ($query) => $query->where('stripe_price_id', $priceId))
            ->first();
    }

    private function notifyOwner(Subscription $subscription, NotificationTypeEnum $type): void
    {
        $owner = $subscription->organization?->owner;

        if ($owner !== null) {
            $this->notifications->notify($owner, $type, ['organization_id' => $subscription->organization_id]);
        }
    }

    private function date(?int $timestamp): ?Carbon
    {
        return $timestamp === null ? null : Carbon::createFromTimestamp($timestamp);
    }
}
