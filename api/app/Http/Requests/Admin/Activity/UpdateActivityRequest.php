<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Activity;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'siret' => ['sometimes', 'nullable', 'string', 'size:14'],
            'siren' => ['sometimes', 'nullable', 'string', 'size:9'],
        ];
    }
}
