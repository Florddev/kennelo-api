<?php

declare(strict_types=1);

namespace App\Http\Requests\Activity;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceTravelFeesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tiers' => ['present', 'array', 'max:20'],
            'tiers.*.up_to_km' => ['required', 'integer', 'min:1', 'max:'.config('activities.max_radius_km'), 'distinct'],
            'tiers.*.fee' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'decimal:0,2'],
        ];
    }
}
