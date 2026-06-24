<?php

declare(strict_types=1);

namespace App\Http\Requests\Pet;

use Illuminate\Foundation\Http\FormRequest;

class StorePetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'animal_type_id' => ['required', 'uuid', 'exists:animal_types,id'],
            'animal_breed_id' => ['sometimes', 'nullable', 'uuid', 'exists:animal_breeds,id'],
            'name' => ['required', 'string', 'max:255'],
            'breed' => ['sometimes', 'nullable', 'string', 'max:255'],
            'birth_date' => ['sometimes', 'nullable', 'date', 'before:today'],
            'sex' => ['sometimes', 'nullable', 'in:male,female,unknown'],
            'weight' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'is_sterilized' => ['sometimes', 'nullable', 'boolean'],
            'has_microchip' => ['sometimes', 'boolean'],
            'microchip_number' => ['sometimes', 'nullable', 'string', 'max:50', 'required_if:has_microchip,true'],
            'adoption_date' => ['sometimes', 'nullable', 'date'],
            'about' => ['sometimes', 'nullable', 'string'],
            'health_notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
