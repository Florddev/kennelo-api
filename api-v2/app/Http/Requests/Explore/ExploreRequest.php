<?php

declare(strict_types=1);

namespace App\Http\Requests\Explore;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Position facultative du client, qui active la section « près de chez vous » et le tri par distance.
 */
class ExploreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lat' => ['required_with:lng', 'numeric', 'between:-90,90'],
            'lng' => ['required_with:lat', 'numeric', 'between:-180,180'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
