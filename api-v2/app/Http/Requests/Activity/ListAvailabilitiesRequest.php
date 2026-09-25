<?php

declare(strict_types=1);

namespace App\Http\Requests\Activity;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * Période lue, d'un an au plus.
 */
class ListAvailabilitiesRequest extends FormRequest
{
    public const int MAX_DAYS = 366;

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
                if ($validator->errors()->isEmpty() && Carbon::parse($this->input('from'))->diffInDays($this->input('to')) >= self::MAX_DAYS) {
                    $validator->errors()->add('to', __('activity.range_too_long', ['days' => self::MAX_DAYS]));
                }
            },
        ];
    }
}
