<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Booking;

use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;

class RefundBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'service_fee' => ['sometimes', 'boolean'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    /**
     * @return numeric-string
     */
    public function amount(): string
    {
        return Money::round((string) $this->validated('amount'));
    }
}
