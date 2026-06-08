<?php

declare(strict_types=1);

namespace App\Http\Requests\Explore;

use App\Models\AnimalType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchExploreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:date_from'],
            'host_type' => ['sometimes', 'nullable', Rule::in(['pro', 'individual'])],
            'min_rating' => ['sometimes', 'nullable', 'numeric', 'between:0,5'],
            'max_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'radius' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'sort' => ['sometimes', 'nullable', Rule::in(['rating', 'distance', 'price'])],
            'lat' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'lng' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];

        foreach (AnimalType::pluck('code') as $code) {
            $rules[$code] = ['sometimes', 'nullable', 'integer', 'min:0'];
        }

        return $rules;
    }
}
