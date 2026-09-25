<?php

declare(strict_types=1);

namespace App\Http\Requests\Pricing;

use App\Enums\WeekDayEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Comment l'activité applique la période : active ou non, séjour minimum, jours fermés, et majoration du tarif
 * de base pour les places sans prix dans la période (+15 : 15 % plus cher, -10 : 10 % moins cher).
 */
class UpdatePeriodSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active' => ['sometimes', 'boolean'],
            'min_stay' => ['nullable', 'integer', 'min:1', 'max:'.config('booking.max_stay_days')],
            'price_modifier_percent' => ['nullable', 'numeric', 'min:-100', 'max:999.99', 'decimal:0,2'],
            'closed_weekdays' => ['sometimes', 'array', 'max:7'],
            'closed_weekdays.*' => ['distinct', 'integer', Rule::enum(WeekDayEnum::class)],
        ];
    }
}
