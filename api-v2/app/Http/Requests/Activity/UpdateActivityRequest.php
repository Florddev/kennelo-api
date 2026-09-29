<?php

declare(strict_types=1);

namespace App\Http\Requests\Activity;

use App\Enums\CancellationPolicyEnum;
use App\Enums\LocationModeEnum;
use App\Http\Requests\Concerns\ValidatesAddress;
use App\Models\Activity;
use App\Rules\SiretOfOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Le métier d'une activité ne change pas. Les règles de lieux et d'espèces sont celles de la création,
 * appliquées à ce que l'activité aura après la modification.
 */
class UpdateActivityRequest extends FormRequest
{
    use ValidatesAddress;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Activity|null $activity */
        $activity = $this->route('activity');
        $organization = $activity?->organization;
        $profession = $activity?->profession()->firstOrFail();

        $locations = $this->has('locations')
            ? (array) $this->input('locations')
            : array_map(fn (LocationModeEnum $location): string => $location->value, $activity?->locations() ?? []);
        $servesAtClient = in_array(LocationModeEnum::AT_CLIENT->value, $locations, true);
        $needsAddress = array_diff($locations, [LocationModeEnum::REMOTE->value]) !== [];

        return [
            'profession_id' => ['prohibited'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'establishment_siret' => ['bail', 'nullable', 'digits:14', new SiretOfOrganization($organization)],
            'timezone' => ['sometimes', 'timezone:all'],
            'locations' => ['sometimes', 'array', 'min:1'],
            'locations.*' => ['distinct', Rule::in(array_map(
                fn (LocationModeEnum $location): string => $location->value,
                $profession?->allowedLocations() ?? LocationModeEnum::cases(),
            ))],
            'service_radius_km' => [
                Rule::requiredIf($servesAtClient && $activity?->service_radius_km === null),
                // Tant que l'activité se déplace, son rayon ne peut pas être effacé.
                Rule::when(! $servesAtClient, 'nullable'),
                'integer',
                'min:1',
                'max:'.config('activities.max_radius_km'),
            ],
            'cancellation_policy' => ['sometimes', Rule::enum(CancellationPolicyEnum::class)],
            'is_active' => ['sometimes', 'boolean'],
            'animal_type_ids' => ['sometimes', 'array', 'min:1'],
            'animal_type_ids.*' => [
                'distinct',
                'uuid',
                Rule::exists('profession_animal_types', 'animal_type_id')->where('profession_id', $profession?->id),
            ],
            ...$this->addressRules(
                required: Rule::requiredIf($needsAddress && $activity?->address_id === null),
                withCoordinates: true,
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'locations.*.in' => __('activity.location_not_allowed'),
            'animal_type_ids.*.exists' => __('activity.animal_type_not_allowed'),
        ];
    }
}
