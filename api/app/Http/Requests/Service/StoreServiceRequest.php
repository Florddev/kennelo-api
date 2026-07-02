<?php

declare(strict_types=1);

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'is_included' => ['sometimes', 'boolean'],
            'price' => ['required', 'numeric', 'min:0'],
            'animal_type_id' => ['required', 'uuid', 'exists:animal_types,id'],
        ];
    }
}
