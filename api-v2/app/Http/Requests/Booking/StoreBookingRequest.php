<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

/**
 * La demande du devis, plus la carte qui paiera. Elle est enregistrée chez Stripe pour les compléments.
 */
class StoreBookingRequest extends QuoteBookingRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'payment_method_id' => ['required', 'string', 'starts_with:pm_', 'max:255'],
        ];
    }
}
