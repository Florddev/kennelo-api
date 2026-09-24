<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubscriptionPaymentStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une échéance d'abonnement, enregistrée à partir des factures Stripe.
 *
 * @property SubscriptionPaymentStatusEnum $status
 */
class SubscriptionPayment extends Model
{
    use HasUuids;

    protected $fillable = [
        'subscription_id',
        'stripe_invoice_id',
        'stripe_payment_intent_id',
        'amount',
        'currency',
        'status',
        'paid_at',
        'invoice_pdf_url',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => SubscriptionPaymentStatusEnum::class,
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
