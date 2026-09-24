<?php

declare(strict_types=1);

namespace App\Http\Requests\Organization;

use App\Enums\OrganizationMemberStatusEnum;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferOwnershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Organization $organization */
        $organization = $this->route('organization');

        return [
            'member_id' => [
                'required',
                'uuid',
                Rule::exists('organization_members', 'id')
                    ->where('organization_id', $organization->id)
                    ->where('status', OrganizationMemberStatusEnum::ACTIVE->value)
                    ->whereNot('user_id', $organization->owner_id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'member_id.exists' => __('organization.transfer_requires_active_member'),
        ];
    }
}
