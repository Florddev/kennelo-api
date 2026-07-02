<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\AdminActionTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAdminActionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'action' => ['sometimes', Rule::enum(AdminActionTypeEnum::class)],
            'admin_id' => ['sometimes', 'uuid'],
            'user_id' => ['sometimes', 'uuid'],
        ];
    }
}
