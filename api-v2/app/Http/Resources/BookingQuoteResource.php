<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Pet;
use App\Services\Booking\BookingQuote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Devis montré au client : le détail de ce qu'il paiera. La commission de l'entreprise n'y figure pas.
 *
 * @property BookingQuote $resource
 */
class BookingQuoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $quote = $this->resource;

        return [
            'activity_id' => $quote->activity->id,
            'start_date' => $quote->startDate->toDateString(),
            'end_date' => $quote->endDate->toDateString(),
            'nights' => $quote->nights,
            'location' => $quote->location->value,
            'units' => array_map(fn (array $unit): array => [
                'unit_type' => ['id' => $unit['unit_type']->id, 'name' => $unit['unit_type']->name],
                'pet_ids' => array_map(fn (Pet $pet): string => $pet->id, $unit['pets']),
                'nights' => $unit['nights'],
                'subtotal' => $unit['subtotal'],
                'price_breakdown' => $unit['breakdown'],
            ], $quote->units),
            'options' => array_map(fn (array $option): array => [
                'service' => ['id' => $option['service']->id, 'name' => $option['service']->name],
                'pet_id' => $option['pet']->id,
                'quantity' => $option['quantity'],
                'unit_price' => $option['unit_price'],
                'subtotal' => $option['subtotal'],
                'is_included' => $option['is_included'],
            ], $quote->options),
            'currency' => $quote->currency,
            'items_amount' => $quote->itemsAmount,
            'travel_fee' => $quote->travelFee,
            'service_fee' => $quote->serviceFee,
            'total_price' => $quote->totalPrice,
            'cancellation_policy' => $quote->cancellationPolicy->value,
        ];
    }
}
