<?php

declare(strict_types=1);

namespace App\Http\Requests\Activity;

use App\Enums\WeekDayEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * La semaine complète, en plages (jour, ouverture, fermeture). Deux plages d'un même jour ne se chevauchent pas.
 */
class ReplaceOpeningHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hours' => ['present', 'array', 'max:50'],
            'hours.*.weekday' => ['required', 'integer', Rule::enum(WeekDayEnum::class)],
            'hours.*.opens_at' => ['required', 'date_format:H:i'],
            'hours.*.closes_at' => ['required', 'date_format:H:i', 'after:hours.*.opens_at'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $overlapping = collect($this->input('hours'))
                    ->groupBy('weekday')
                    ->contains(fn ($ranges): bool => $ranges
                        ->sortBy('opens_at')
                        ->values()
                        ->sliding(2)
                        ->contains(fn ($pair): bool => $pair->last()['opens_at'] < $pair->first()['closes_at']));

                if ($overlapping) {
                    $validator->errors()->add('hours', __('activity.opening_hours_overlap'));
                }
            },
        ];
    }
}
