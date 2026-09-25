<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Profession;

use App\Http\Requests\Concerns\ValidatesTranslations;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Le code sert de filtre dans la recherche et dans les liens du front : il ne change pas.
 */
class UpdateProfessionCategoryRequest extends FormRequest
{
    use ValidatesTranslations;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['prohibited'],
            ...$this->translationRules('name', 'sometimes', 100),
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
