<?php

declare(strict_types=1);

namespace App\Http\Requests\Hosting;

use Illuminate\Foundation\Http\FormRequest;

/**
 * microchip : le numéro lu sur la puce d'un animal scanné.
 */
class ListInCarePetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'microchip' => ['sometimes', 'string', 'max:100'],
        ];
    }
}
