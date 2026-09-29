<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'string', 'max:100'],
            'role' => ['sometimes', Rule::enum(RoleEnum::class)],
            'sort_by' => ['sometimes', 'string', 'in:first_name,last_name,email,created_at'],
            'sort_dir' => ['sometimes', 'string', 'in:asc,desc'],
        ];
    }
}
