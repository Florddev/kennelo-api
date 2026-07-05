<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Activity;

use App\Enums\ActivityStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListActivitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', Rule::enum(ActivityStatusEnum::class)],
            'professional' => ['sometimes', 'boolean'],
            'department' => ['sometimes', 'string', 'max:3'],
            'search' => ['sometimes', 'string', 'max:255'],
            'sort_by' => ['sometimes', 'string', Rule::in(['name', 'created_at', 'status'])],
            'sort_direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
        ];
    }
}
