<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Activity;

use App\Enums\DocumentStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListActivityDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', Rule::enum(DocumentStatusEnum::class)],
            'activity_id' => ['sometimes', 'uuid'],
        ];
    }
}
