<?php

declare(strict_types=1);

namespace App\Http\Requests\Establishment;

use App\Enums\AvailabilityStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(AvailabilityStatusEnum::class)],
            'note' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
