<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayoutStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPayout extends Model
{
    use HasUuids;

    protected $fillable = [
        'booking_id',
        'stripe_transfer_id',
        'activity_stripe_account_id',
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

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
