<?php

declare(strict_types=1);

namespace App\Http\Requests\Pet;

use App\Enums\PetCoatTypeEnum;
use App\Enums\PetSizeClassEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StorePetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $animalTypeId = Str::isUuid($this->input('animal_type_id')) ? $this->input('animal_type_id') : null;

        return [
            'animal_type_id' => ['required', 'uuid', 'exists:animal_types,id'],
            'animal_breed_id' => [
                'sometimes',
                'nullable',
                'uuid',
                Rule::exists('animal_breeds', 'id')->where('animal_type_id', $animalTypeId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'size_class' => ['sometimes', 'nullable', Rule::enum(PetSizeClassEnum::class)],
            'coat_type' => ['sometimes', 'nullable', Rule::enum(PetCoatTypeEnum::class)],
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
