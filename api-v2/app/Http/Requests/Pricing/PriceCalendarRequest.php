<?php

declare(strict_types=1);

namespace App\Http\Requests\Pricing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PriceCalendarRequest extends FormRequest
{
    public const int MAX_DAYS = 93;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isEmpty() && $this->date('from')?->diffInDays($this->date('to')) >= self::MAX_DAYS) {
                    $validator->errors()->add('to', __('activity.range_too_long', ['days' => self::MAX_DAYS]));
                }
            },
        ];
    }
}
