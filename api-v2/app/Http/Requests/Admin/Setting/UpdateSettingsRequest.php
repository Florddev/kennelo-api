<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Setting;

use App\Services\Setting\SettingService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(SettingService $settings): array
    {
        $rules = [
            'values' => ['required', 'array'],
        ];

        foreach ($settings->definitions() as $key => $definition) {
            $rules['values.'.$key] = ['sometimes', ...$definition['rules']];
        }

        return $rules;
    }
}
