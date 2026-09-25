<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayoutStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Versement d'une réservation au compte Stripe Connect de l'entreprise : un seul par réservation.
 *
 * @property string $id
 * @property string $booking_id
 * @property string $stripe_transfer_id
 * @property string $stripe_account_id
 * @property numeric-string $amount
 * @property string $currency
 * @property PayoutStatusEnum $status
 * @property Carbon|null $transferred_at
 */
class BookingPayout extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'booking_id',
        'stripe_transfer_id',
        'stripe_account_id',
        'amount',
        'currency',
        'status',
        'transferred_at',
        'estimated_arrival',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PayoutStatusEnum::class,
            'transferred_at' => 'datetime',
            'estimated_arrival' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
