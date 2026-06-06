<?php

declare(strict_types=1);

namespace App\Http\Requests\Activity;

use App\Enums\WeekDayEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpsertCycleSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.*.animal_type_id' => ['required', 'uuid', 'exists:animal_types,id'],
            'settings.*.max_capacity' => ['required', 'integer', 'min:1'],
            'settings.*.price' => ['required', 'numeric', 'min:0'],
            'settings.*.sum_weekdays' => ['sometimes', 'integer', 'min:0', 'max:'.WeekDayEnum::ALL],
        ];
    }
}
