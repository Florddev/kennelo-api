<?php

declare(strict_types=1);

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuoteBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'activity_id' => ['required', 'uuid', Rule::exists('activities', 'id')->where('is_active', true)],
            'check_in_date' => ['required', 'date', 'after_or_equal:today'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
            'pet_ids' => ['required', 'array', 'min:1'],
            'pet_ids.*' => ['uuid', Rule::exists('pets', 'id')->where('user_id', $this->user()?->id)],
            'service_ids' => ['sometimes', 'array'],
            'service_ids.*' => ['uuid', Rule::exists('services', 'id')->where('activity_id', $this->input('activity_id'))],
        ];
    }
}
