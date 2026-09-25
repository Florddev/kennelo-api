<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use App\Models\Booking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * L'heure de l'option (ISO 8601, avec son fuseau) et la ressource de l'entreprise qui la réalise.
 */
class ScheduleBookingItemRequest extends FormRequest
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
            'starts_at' => ['required', 'date'],
            'resource_id' => ['required', 'uuid', Rule::exists('resources', 'id')->where('organization_id', $booking->organization_id)],
        ];
    }
}
