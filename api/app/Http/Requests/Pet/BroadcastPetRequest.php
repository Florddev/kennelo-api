<?php

declare(strict_types=1);

namespace App\Http\Requests\Pet;

use Illuminate\Foundation\Http\FormRequest;

class BroadcastPetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'microchip_number' => ['required', 'string', 'max:100'],
            'scanner_code' => ['required', 'string', 'exists:scanners,code'],
        ];
    }
}
