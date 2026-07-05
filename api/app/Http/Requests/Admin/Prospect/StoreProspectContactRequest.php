<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Prospect;

use App\Enums\ProspectContactTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProspectContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ProspectContactTypeEnum::class)],
            'contacted_at' => ['sometimes', 'date'],
            'outcome' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
