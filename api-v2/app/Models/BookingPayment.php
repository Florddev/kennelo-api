<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentKindEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Un PaymentIntent Stripe d'une réservation. Son montant ne change jamais : un ajustement ajoute un
 * paiement complémentaire ou un remboursement.
 *
 * @property string $id
 * @property string $booking_id
 * @property PaymentKindEnum $kind
 * @property numeric-string $amount
 * @property string $currency
 * @property PaymentStatusEnum $status
 * @property string $stripe_payment_intent_id
 * @property string|null $stripe_charge_id
 * @property Carbon|null $paid_at
 * @property-read Booking|null $booking
 */
class BookingPayment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'kind',
        'amount',
        'currency',
        'status',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'kind' => PaymentKindEnum::class,
            'amount' => 'decimal:2',
            'status' => PaymentStatusEnum::class,
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Part du paiement encore remboursable.
     *
     * @return numeric-string
     */
    public function refundableAmount(): string
    {
        $refunded = $this->refunds->reduce(fn (string $sum, BookingRefund $refund): string => bcadd($sum, $refund->amount, 2), '0.00');

        return bcsub($this->amount, $refunded, 2);
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return HasMany<BookingRefund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(BookingRefund::class);
    }

    /**
     * @return HasMany<BookingItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }
}
