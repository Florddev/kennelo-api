<?php

declare(strict_types=1);

namespace App\Http\Requests\Pricing;

use App\Enums\WeekDayEnum;
use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * La grille complète de l'activité pour la période : un prix par place, pour tous les jours (weekday absent)
 * ou pour un jour de la semaine. Une place et un jour n'ont qu'un prix.
 */
class ReplacePeriodPricesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Activity $activity */
        $activity = $this->route('activity');

        return [
            'prices' => ['present', 'array', 'max:500'],
            'prices.*.unit_type_id' => ['required', 'uuid', Rule::exists('activity_unit_types', 'id')->where('activity_id', $activity->id)],
            'prices.*.weekday' => ['nullable', 'integer', Rule::enum(WeekDayEnum::class)],
            'prices.*.price' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
            'prices.*.extra_animal_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
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

                $keys = array_map(
                    fn (array $line): string => $line['unit_type_id'].'|'.($line['weekday'] ?? 'all'),
                    (array) $this->input('prices'),
                );

                if (count($keys) !== count(array_unique($keys))) {
                    $validator->errors()->add('prices', __('booking.duplicate_price'));
                }
            },
        ];
    }
}
