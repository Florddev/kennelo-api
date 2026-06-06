<?php

declare(strict_types=1);

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class ListReviewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'reviewer_type' => ['sometimes', 'string', 'in:user,activity'],
            'min_rating' => ['sometimes', 'numeric', 'between:1,5'],
        ];
    }
}
