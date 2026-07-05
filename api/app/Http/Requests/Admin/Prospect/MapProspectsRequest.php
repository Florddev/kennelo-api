<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Prospect;

use App\Enums\ProspectStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MapProspectsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(ProspectStatusEnum::class)],
            'department' => ['sometimes', 'string', 'max:3'],
            'registered' => ['sometimes', 'boolean'],
            'bbox' => ['sometimes', 'array', 'size:4'],
            'bbox.*' => ['numeric'],
        ];
    }
}
