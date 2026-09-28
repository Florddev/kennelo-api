<?php

declare(strict_types=1);

namespace App\Http\Requests\Review;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Note de 1 à 5, par demi-points. Le retour privé n'est lu que par la partie notée.
 */
class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'overall_rating' => ['required', 'numeric', 'between:1,5', 'multiple_of:0.5'],
            'comment' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'private_feedback' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'would_recommend' => ['sometimes', 'boolean'],
        ];
    }
}
