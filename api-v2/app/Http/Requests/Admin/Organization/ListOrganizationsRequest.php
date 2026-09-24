<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Organization;

use App\Enums\OrganizationStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListOrganizationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', Rule::enum(OrganizationStatusEnum::class)],
            'search' => ['sometimes', 'string', 'max:100'],
        ];
    }
}
