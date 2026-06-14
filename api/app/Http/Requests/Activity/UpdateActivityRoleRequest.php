<?php

declare(strict_types=1);

namespace App\Http\Requests\Activity;

use App\Enums\ActivityPermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateActivityRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', Rule::in(ActivityPermissionEnum::values())],
        ];
    }
}
