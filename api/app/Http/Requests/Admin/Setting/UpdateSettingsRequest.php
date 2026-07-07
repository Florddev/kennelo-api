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

    public function rules(): array
    {
        $rules = [
            'values' => ['required', 'array'],
        ];

        foreach (SettingService::DEFINITIONS as $key => $definition) {
            $rules['values.'.$key] = array_merge(['sometimes'], $definition['rules']);
        }

        return $rules;
    }
}
