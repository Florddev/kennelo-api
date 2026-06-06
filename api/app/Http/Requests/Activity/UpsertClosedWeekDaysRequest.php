<?php

declare(strict_types=1);

namespace App\Http\Requests\Activity;

use App\Enums\WeekDayEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpsertClosedWeekDaysRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sum_weekdays' => ['required', 'integer', 'min:0', 'max:'.WeekDayEnum::ALL],
        ];
    }
}
