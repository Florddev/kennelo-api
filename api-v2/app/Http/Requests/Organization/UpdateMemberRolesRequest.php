<?php

declare(strict_types=1);

namespace App\Http\Requests\Organization;

use App\Enums\OrganizationRoleEnum;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Remplace tous les rôles d'un membre. Chaque rôle lié à une activité vise une activité de la même
 * entreprise ; un membre a au plus un rôle sur toute l'entreprise et un rôle par activité.
 */
class UpdateMemberRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Organization|null $organization */
        $organization = $this->route('organization');

        [$activityScoped, $organizationWide] = collect(OrganizationRoleEnum::cases())
            ->partition(fn (OrganizationRoleEnum $role): bool => $role->isActivityScoped());

        return [
            'roles' => ['present', 'array', 'max:50'],
            'roles.*.role' => ['required', Rule::enum(OrganizationRoleEnum::class)],
            'roles.*.activity_id' => [
                'nullable',
                'required_if:roles.*.role,'.$activityScoped->pluck('value')->implode(','),
                'prohibited_if:roles.*.role,'.$organizationWide->pluck('value')->implode(','),
                'uuid',
                Rule::exists('activities', 'id')->where('organization_id', $organization?->id)->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'roles.*.activity_id.required_if' => __('team.role_requires_activity'),
            'roles.*.activity_id.prohibited_if' => __('team.role_forbids_activity'),
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

                $scopes = collect($this->input('roles', []))->map(fn (array $role): ?string => $role['activity_id'] ?? null);

                if ($scopes->duplicates()->isNotEmpty()) {
                    $validator->errors()->add('roles', __('team.duplicate_scope'));
                }
            },
        ];
    }
}
