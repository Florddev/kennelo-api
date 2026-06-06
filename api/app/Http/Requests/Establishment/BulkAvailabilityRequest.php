<?php

declare(strict_types=1);

namespace App\Http\Requests\Establishment;

use App\Enums\AvailabilityStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dates' => ['required', 'array', 'min:1', 'max:365'],
            'dates.*' => ['required', 'date', 'date_format:Y-m-d'],
            'status' => ['required', Rule::enum(AvailabilityStatusEnum::class)],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
