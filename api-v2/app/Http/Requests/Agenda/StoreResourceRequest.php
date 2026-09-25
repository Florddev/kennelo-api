<?php

declare(strict_types=1);

namespace App\Http\Requests\Agenda;

use App\Enums\OrganizationMemberStatusEnum;
use App\Enums\ResourceTypeEnum;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Une personne est un membre actif de l'équipe, qui n'a pas encore sa fiche ; un équipement ou un espace n'est lié
 * à personne.
 */
class StoreResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Organization $organization */
        $organization = $this->route('organization');
        $isStaff = $this->input('type') === ResourceTypeEnum::STAFF->value;

        return [
            'type' => ['required', Rule::enum(ResourceTypeEnum::class)],
            'name' => ['required', 'string', 'max:100'],
            'organization_member_id' => [
                Rule::requiredIf($isStaff),
                Rule::prohibitedIf(! $isStaff),
                'nullable',
                'uuid',
                Rule::exists('organization_members', 'id')
                    ->where('organization_id', $organization->id)
                    ->where('status', OrganizationMemberStatusEnum::ACTIVE->value),
                Rule::unique('resources', 'organization_member_id'),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'organization_member_id.unique' => __('agenda.member_already_has_resource'),
        ];
    }
}
