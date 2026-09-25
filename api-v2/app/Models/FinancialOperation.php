<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FinancialOperationTypeEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Ligne du journal financier : écrite une fois, jamais modifiée.
 *
 * @property string $id
 * @property string|null $booking_id
 * @property FinancialOperationTypeEnum $type
 * @property numeric-string|null $amount
 * @property string|null $currency
 * @property string|null $stripe_reference
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 */
class FinancialOperation extends Model
{
    use HasUuids;

    protected $fillable = [
        'booking_id',
        'type',
        'amount',
        'currency',
        'stripe_reference',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => FinancialOperationTypeEnum::class,
            'amount' => 'decimal:2',
            'metadata' => 'array',
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
