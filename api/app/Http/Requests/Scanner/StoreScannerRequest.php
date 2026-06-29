<?php

declare(strict_types=1);

namespace App\Http\Requests\Scanner;

use Illuminate\Foundation\Http\FormRequest;

class StoreScannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:100', 'unique:scanners,code'],
            'name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
