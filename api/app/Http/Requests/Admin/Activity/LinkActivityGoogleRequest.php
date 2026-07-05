<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Activity;

use Illuminate\Foundation\Http\FormRequest;

class LinkActivityGoogleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'google_place_id' => ['required', 'string', 'max:255'],
            'google_rating' => ['sometimes', 'nullable', 'numeric', 'between:0,5'],
            'google_reviews_count' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'google_maps_url' => ['sometimes', 'nullable', 'url', 'max:255'],
        ];
    }
}
