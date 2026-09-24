<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLocaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $availableLocales = explode(',', config('app.available_locales', 'en'));

        return [
            'locale' => ['required', 'string', 'in:'.implode(',', $availableLocales)],
        ];
    }
}
