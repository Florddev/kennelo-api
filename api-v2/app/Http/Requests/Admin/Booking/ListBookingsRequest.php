<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Booking;

use App\Enums\BookingStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListBookingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', Rule::enum(BookingStatusEnum::class)],
            'organization_id' => ['sometimes', 'uuid'],
            'activity_id' => ['sometimes', 'uuid'],
            'disputed' => ['sometimes', 'boolean'],
            'search' => ['sometimes', 'string', 'max:100'],
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }
}
