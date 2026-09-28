<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CancelBookingRequest extends FormRequest
{
    public const string REFUND_FULL = 'full';

    public const string REFUND_POLICY = 'policy';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'refund' => ['sometimes', 'string', 'in:'.self::REFUND_FULL.','.self::REFUND_POLICY],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function appliesPolicy(): bool
    {
        return $this->validated('refund', self::REFUND_FULL) === self::REFUND_POLICY;
    }
}
