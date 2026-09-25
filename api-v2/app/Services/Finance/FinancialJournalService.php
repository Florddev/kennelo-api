<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\FinancialOperationTypeEnum;
use App\Models\Booking;
use App\Models\FinancialOperation;

/**
 * Journal financier des réservations : une ligne par mouvement d'argent ou changement de statut, jamais modifiée.
 */
class FinancialJournalService
{
    /**
     * @param  numeric-string|null  $amount
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        FinancialOperationTypeEnum $type,
        Booking $booking,
        ?string $amount = null,
        ?string $stripeReference = null,
        array $metadata = [],
    ): FinancialOperation {
        return FinancialOperation::create([
            'booking_id' => $booking->id,
            'type' => $type,
            'amount' => $amount,
            'currency' => $amount === null ? null : $booking->currency,
            'stripe_reference' => $stripeReference,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }
}
