<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FinancialOperationTypeEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
