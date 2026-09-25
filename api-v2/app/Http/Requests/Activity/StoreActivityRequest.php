<?php

declare(strict_types=1);

namespace App\Http\Requests\Activity;

use App\Enums\CancellationPolicyEnum;
use App\Enums\LocationModeEnum;
use App\Http\Requests\Concerns\ValidatesAddress;
use App\Models\Organization;
use App\Models\Profession;
use App\Rules\SiretOfOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Les lieux pratiqués sont pris parmi ceux que le métier autorise, et les espèces parmi celles du métier.
 * Se déplacer chez le client demande un rayon ; recevoir ou se déplacer demande une adresse géolocalisée.
 */
class StoreActivityRequest extends FormRequest
{
    use ValidatesAddress;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Organization $organization */
        $organization = $this->route('organization');
        $profession = Profession::query()->active()->find($this->input('profession_id'));
        $locations = (array) $this->input('locations', []);

        return [
            'profession_id' => ['required', 'uuid', Rule::exists('professions', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'establishment_siret' => ['bail', 'nullable', 'digits:14', new SiretOfOrganization($organization)],
            'timezone' => ['sometimes', 'timezone:all'],
            'locations' => ['required', 'array', 'min:1'],
            'locations.*' => ['distinct', Rule::in(array_map(
                fn (LocationModeEnum $location): string => $location->value,
                $profession?->allowedLocations() ?? LocationModeEnum::cases(),
            ))],
            'service_radius_km' => [
                Rule::requiredIf(in_array(LocationModeEnum::AT_CLIENT->value, $locations, true)),
                'nullable',
                'integer',
                'min:1',
                'max:'.config('activities.max_radius_km'),
            ],
            'cancellation_policy' => ['sometimes', Rule::enum(CancellationPolicyEnum::class)],
            'animal_type_ids' => ['required', 'array', 'min:1'],
            'animal_type_ids.*' => [
                'distinct',
                'uuid',
                Rule::exists('profession_animal_types', 'animal_type_id')->where('profession_id', $profession?->id),
            ],
            ...$this->addressRules(
                required: Rule::requiredIf(array_diff($locations, [LocationModeEnum::REMOTE->value]) !== []),
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
