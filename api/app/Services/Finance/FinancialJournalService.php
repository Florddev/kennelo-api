<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\FinancialOperationTypeEnum;
use App\Models\Booking;
use App\Models\FinancialOperation;

class FinancialJournalService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        FinancialOperationTypeEnum $type,
        ?Booking $booking = null,
        ?string $amount = null,
        ?string $stripeReference = null,
        array $metadata = []
    ): void {
        FinancialOperation::create([
            'booking_id' => $booking?->id,
            'type' => $type,
            'amount' => $amount,
            'currency' => $amount !== null ? (string) str((string) config('services.stripe.currency', 'eur'))->upper() : null,
            'stripe_reference' => $stripeReference,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }
}
