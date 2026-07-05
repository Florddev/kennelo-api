<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Prospect;

use App\Enums\ProspectStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListProspectsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', Rule::enum(ProspectStatusEnum::class)],
            'department' => ['sometimes', 'string', 'max:3'],
            'region' => ['sometimes', 'string', 'max:100'],
            'assigned_to' => ['sometimes', 'uuid'],
            'registered' => ['sometimes', 'boolean'],
            'min_rating' => ['sometimes', 'numeric', 'min:0', 'max:5'],
            'search' => ['sometimes', 'string', 'max:255'],
            'sort_by' => ['sometimes', 'string', Rule::in(['name', 'created_at', 'google_rating', 'status'])],
            'sort_direction' => ['sometimes', 'string', Rule::in(['asc', 'desc'])],
        ];
    }
}
