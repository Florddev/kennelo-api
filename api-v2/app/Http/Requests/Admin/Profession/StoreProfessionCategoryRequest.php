<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Profession;

use App\Http\Requests\Concerns\ValidatesTranslations;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProfessionCategoryRequest extends FormRequest
{
    use ValidatesTranslations;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'alpha_dash:ascii', 'max:50', Rule::unique('profession_categories', 'code')],
            ...$this->translationRules('name', 'required', 100),
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
