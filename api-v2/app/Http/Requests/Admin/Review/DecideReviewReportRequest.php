<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Review;

use App\Enums\ReviewReportStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * reviewed : examiné, l'avis reste ; rejected : signalement infondé ; removed : l'avis est retiré.
 */
class DecideReviewReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(ReviewReportStatusEnum::decisions())],
        ];
    }
}
