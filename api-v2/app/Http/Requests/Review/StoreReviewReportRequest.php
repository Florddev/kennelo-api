<?php

declare(strict_types=1);

namespace App\Http\Requests\Review;

use App\Enums\ReviewReportReasonEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReviewReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::enum(ReviewReportReasonEnum::class)],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
