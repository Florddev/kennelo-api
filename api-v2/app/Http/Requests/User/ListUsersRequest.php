<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

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
            'role' => ['sometimes', 'string', 'max:50'],
            'sort_by' => ['sometimes', 'string', 'in:first_name,last_name,email,created_at'],
            'sort_dir' => ['sometimes', 'string', 'in:asc,desc'],
        ];
    }
}
