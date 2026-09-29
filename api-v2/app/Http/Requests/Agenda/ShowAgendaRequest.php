<?php

declare(strict_types=1);

namespace App\Http\Requests\Agenda;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Période de l'agenda (dates dans le fuseau de l'activité, ou celui par défaut), limitée à une activité de
 * l'entreprise ou à certaines de ses ressources.
 */
class ShowAgendaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Organization|null $organization */
        $organization = $this->route('organization');

        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'activity_id' => ['sometimes', 'uuid', Rule::exists('activities', 'id')->where('organization_id', $organization?->id)->whereNull('deleted_at')],
            'resource_ids' => ['sometimes', 'array', 'max:50'],
            'resource_ids.*' => ['distinct', 'uuid'],
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
