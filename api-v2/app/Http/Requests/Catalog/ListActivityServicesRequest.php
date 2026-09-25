<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Avec l'un de ses animaux, le client voit le prix et la durée de chaque prestation pour lui.
 */
class ListActivityServicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pet_id' => ['sometimes', 'uuid', Rule::exists('pets', 'id')->where('user_id', $this->user()?->id)],
        ];
    }
}
