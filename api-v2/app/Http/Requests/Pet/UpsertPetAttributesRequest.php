<?php

declare(strict_types=1);

namespace App\Http\Requests\Pet;

use App\Models\AttributeOption;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpsertPetAttributesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attributes' => ['required', 'array', 'min:1'],
            'attributes.*.attribute_definition_id' => ['required', 'uuid', 'exists:attribute_definitions,id'],
            'attributes.*.attribute_option_id' => ['sometimes', 'nullable', 'uuid', 'exists:attribute_options,id'],
            'attributes.*.value_text' => ['sometimes', 'nullable', 'string'],
            'attributes.*.value_integer' => ['sometimes', 'nullable', 'integer'],
            'attributes.*.value_decimal' => ['sometimes', 'nullable', 'numeric'],
            'attributes.*.value_boolean' => ['sometimes', 'nullable', 'boolean'],
            'attributes.*.value_date' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $valueFields = ['value_text', 'value_integer', 'value_decimal', 'value_boolean', 'value_date'];

            foreach ($this->input('attributes', []) as $index => $item) {
                $presentCount = collect($valueFields)
                    ->filter(fn (string $field) => array_key_exists($field, $item) && $item[$field] !== null)
                    ->count();

                if ($presentCount !== 1) {
                    $validator->errors()->add(
                        "attributes.$index",
                        'Exactly one value field must be provided (value_text, value_integer, value_decimal, value_boolean, or value_date).'
                    );
                }

                if (filled($item['attribute_option_id'] ?? null) && filled($item['attribute_definition_id'] ?? null)) {
                    $optionBelongs = AttributeOption::where('id', $item['attribute_option_id'])
                        ->where('attribute_definition_id', $item['attribute_definition_id'])
                        ->exists();

                    if (! $optionBelongs) {
                        $validator->errors()->add(
                            "attributes.$index.attribute_option_id",
                            'The selected option does not belong to the specified attribute definition.'
                        );
                    }
                }
            }
        });
    }
}
