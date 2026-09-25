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
            'profession_id' => ['sometimes', 'uuid'],
            'search' => ['sometimes', 'string', 'max:100'],
        ];
    }
}
