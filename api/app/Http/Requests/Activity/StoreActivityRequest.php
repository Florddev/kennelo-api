<?php

declare(strict_types=1);

namespace App\Http\Requests\Activity;

use App\Enums\ActivityTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['sometimes', 'nullable', new Enum(ActivityTypeEnum::class)],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'siret' => ['sometimes', 'nullable', 'string', 'max:14'],
            'timezone' => ['sometimes', 'nullable', 'string', 'timezone:all'],
            'address' => ['sometimes', 'nullable', 'array'],
            'address.line1' => ['required_with:address', 'string', 'max:255'],
            'address.line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address.city' => ['required_with:address', 'string', 'max:100'],
            'address.postal_code' => ['required_with:address', 'string', 'max:20'],
            'address.region' => ['sometimes', 'nullable', 'string', 'max:100'],
            'address.country' => ['required_with:address', 'string', 'max:100'],
        ];
    }
}
