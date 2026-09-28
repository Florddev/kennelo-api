<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RefundReasonEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Remboursement d'un paiement. La part des frais Kennelo (service_fee_amount) est séparée pour émettre
 * le bon avoir chez chaque émetteur.
 *
 * @property string $id
 * @property string $booking_payment_id
 * @property numeric-string $amount
 * @property numeric-string $service_fee_amount
 * @property RefundReasonEnum $reason
 * @property string|null $stripe_refund_id
 * @property Carbon|null $refunded_at
 * @property string|null $created_by
 */
class BookingRefund extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'amount',
        'service_fee_amount',
        'reason',
        'stripe_refund_id',
        'refunded_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'service_fee_amount' => 'decimal:2',
            'reason' => RefundReasonEnum::class,
            'refunded_at' => 'datetime',
        ];
    }

    /**
     * Part remboursée des prestations, frais Kennelo déduits.
     *
     * @return numeric-string
     */
    public function itemsAmount(): string
    {
        return bcsub($this->amount, $this->service_fee_amount, 2);
    }

    /**
     * @return BelongsTo<BookingPayment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(BookingPayment::class, 'booking_payment_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Avoirs émis pour ce remboursement : chez l'entreprise, et chez Kennelo pour sa part des frais.
     *
     * @return HasMany<Invoice, $this>
     */
    public function creditNotes(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
