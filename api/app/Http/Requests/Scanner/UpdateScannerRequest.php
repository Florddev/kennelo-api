<?php

declare(strict_types=1);

namespace App\Http\Requests\Scanner;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
