<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'establishment_id' => ['required', 'uuid', Rule::exists('establishments', 'id')->where('is_active', true)],
            'check_in_date' => ['required', 'date', 'after_or_equal:today'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'pet_ids' => ['required', 'array', 'min:1'],
            'pet_ids.*' => ['uuid', Rule::exists('pets', 'id')->where('user_id', $this->user()?->id)],
            'service_ids' => ['sometimes', 'array'],
            'service_ids.*' => ['uuid', Rule::exists('services', 'id')->where('establishment_id', $this->input('establishment_id'))],
            'special_requests' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'payment_method_id' => ['required', 'string'],
            'save_payment_method' => ['sometimes', 'boolean'],
        ];
    }
}
