<?php

declare(strict_types=1);

namespace App\Http\Requests\Stay;

use App\Enums\BookingModeEnum;
use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Les espèces d'une place sont prises parmi celles de l'activité. Seule une activité de séjour a des places.
 */
class StoreUnitTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Activity|null $activity */
        $activity = $this->route('activity');

        return [
            'name' => ['required', 'string', 'max:100'],
            'animal_type_ids' => ['required', 'array', 'min:1'],
            ...self::unitRules($activity),
        ];
    }

    /**
     * @return array<string, list<mixed>>
     */
    public static function unitRules(?Activity $activity): array
    {
        return [
            'description' => ['nullable', 'string', 'max:2000'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'max_animals_per_unit' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:1000'],
            'animal_type_ids.*' => [
                'distinct',
                'uuid',
                Rule::exists('activity_animal_types', 'animal_type_id')->where('activity_id', $activity?->id),
            ],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->activity()->loadMissing('profession')->profession?->booking_mode !== BookingModeEnum::STAY) {
                    $validator->errors()->add('activity', __('booking.stay_only'));
                }
            },
        ];
    }

    private function activity(): Activity
    {
        /** @var Activity */
        return $this->route('activity');
    }
}
