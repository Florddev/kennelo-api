<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Une option de séjour pour un animal de la réservation.
 */
class StoreBookingItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Booking $booking */
        $booking = $this->route('booking');

        return [
            'service_id' => ['required', 'uuid'],
            'pet_id' => ['required', 'uuid', Rule::exists('booking_pets', 'pet_id')->where('booking_id', $booking->id)],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:20'],
        ];
    }
}
