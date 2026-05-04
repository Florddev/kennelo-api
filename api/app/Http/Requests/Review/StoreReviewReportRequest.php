<?php

declare(strict_types=1);

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'in:inappropriate,offensive,fake,spam,other'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
