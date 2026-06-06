<?php

declare(strict_types=1);

namespace App\Http\Requests\Activity;

use App\Enums\AvailabilityStatusEnum;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $start = $this->input('start_date');
                    if ($start && abs(Carbon::parse($value)->diffInDays(Carbon::parse($start))) > 365) {
                        $fail('The date range cannot exceed 365 days.');
                    }
                },
            ],
            'status' => ['required', Rule::enum(AvailabilityStatusEnum::class)],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
