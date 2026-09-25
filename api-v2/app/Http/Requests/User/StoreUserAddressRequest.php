<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Http\Requests\Concerns\ValidatesAddress;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserAddressRequest extends FormRequest
{
    use ValidatesAddress;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:50'],
            'is_default' => ['sometimes', 'boolean'],
            ...$this->addressRules(required: true),
        ];
    }
}
