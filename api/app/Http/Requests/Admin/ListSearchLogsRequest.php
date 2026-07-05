<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ListSearchLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'department' => ['sometimes', 'string', 'max:3'],
            'search' => ['sometimes', 'string', 'max:255'],
            'from' => ['sometimes', 'date'],
        ];
    }
}
