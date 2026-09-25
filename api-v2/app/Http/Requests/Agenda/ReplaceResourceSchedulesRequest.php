<?php

declare(strict_types=1);

namespace App\Http\Requests\Agenda;

use App\Enums\WeekDayEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * La semaine complète de la ressource dans l'activité, en plages (jour, début, fin). Deux plages d'un même jour
 * ne se chevauchent pas.
 */
class ReplaceResourceSchedulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedules' => ['present', 'array', 'max:50'],
            'schedules.*.weekday' => ['required', 'integer', Rule::enum(WeekDayEnum::class)],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time' => ['required', 'date_format:H:i', 'after:schedules.*.start_time'],
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

                $overlapping = collect($this->input('schedules'))
                    ->groupBy('weekday')
                    ->contains(fn ($ranges): bool => $ranges
                        ->sortBy('start_time')
                        ->values()
                        ->sliding(2)
                        ->contains(fn ($pair): bool => $pair->last()['start_time'] < $pair->first()['end_time']));

                if ($overlapping) {
                    $validator->errors()->add('schedules', __('agenda.schedule_overlap'));
                }
            },
        ];
    }
}
