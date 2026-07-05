<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Prospect;

use Illuminate\Foundation\Http\FormRequest;

class ImportProspectsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'location' => ['required', 'string', 'max:255'],
            'search_terms' => ['sometimes', 'array', 'min:1'],
            'search_terms.*' => ['string', 'max:255'],
            'max_results' => ['sometimes', 'integer', 'min:1', 'max:500'],
            'sync' => ['sometimes', 'boolean'],
        ];
    }
}
