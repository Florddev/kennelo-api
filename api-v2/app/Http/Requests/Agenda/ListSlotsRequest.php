<?php

declare(strict_types=1);

namespace App\Http\Requests\Agenda;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Créneaux d'une prestation sur une période (dates dans le fuseau de l'activité). Le client connecté peut préciser
 * ses animaux, pour la durée exacte du rendez-vous, et la personne qu'il souhaite.
 */
class ListSlotsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'uuid'],
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'pet_ids' => ['sometimes', 'array', 'max:'.config('agenda.max_pets_per_appointment')],
            'pet_ids.*' => ['distinct', 'uuid', Rule::exists('pets', 'id')->where('user_id', $this->user()?->id)],
            'resource_id' => ['sometimes', 'uuid'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $maxDays = (int) config('agenda.max_range_days');

                if ($validator->errors()->isEmpty() && $this->date('from')?->diffInDays($this->date('to')) >= $maxDays) {
                    $validator->errors()->add('to', __('activity.range_too_long', ['days' => $maxDays]));
                }
            },
        ];
    }
}
