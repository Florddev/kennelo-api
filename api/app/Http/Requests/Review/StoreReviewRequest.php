<?php

declare(strict_types=1);

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'overall_rating' => ['required', 'numeric', 'between:1,5'],
            'comment' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'private_feedback' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'would_recommend' => ['sometimes', 'boolean'],
            'criteria_scores' => ['sometimes', 'array'],
            'criteria_scores.*.code' => ['required_with:criteria_scores', 'string', 'exists:review_criteria_definitions,code'],
            'criteria_scores.*.score' => ['required_with:criteria_scores', 'numeric', 'between:1,5'],
        ];
    }
}
