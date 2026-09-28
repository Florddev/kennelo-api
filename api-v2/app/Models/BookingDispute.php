<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DisputeStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $booking_id
 * @property string $booking_payment_id
 * @property string $stripe_dispute_id
 * @property numeric-string $amount
 * @property string $currency
 * @property string $reason
 * @property DisputeStatusEnum $status
 * @property Carbon|null $evidence_due_by
 * @property numeric-string $recovered_amount
 * @property Carbon|null $closed_at
 * @property-read Booking|null $booking
 * @property-read BookingPayment|null $payment
 */
class BookingDispute extends Model
{
    use HasUuids;

    protected $fillable = [
        'booking_payment_id',
        'stripe_dispute_id',
        'amount',
        'currency',
        'reason',
        'status',
        'evidence_due_by',
        'recovered_amount',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'recovered_amount' => 'decimal:2',
            'status' => DisputeStatusEnum::class,
            'evidence_due_by' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', DisputeStatusEnum::open());
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<BookingPayment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(BookingPayment::class, 'booking_payment_id');
    }
}
