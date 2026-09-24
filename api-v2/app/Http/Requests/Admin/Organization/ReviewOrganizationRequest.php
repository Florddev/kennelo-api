<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Organization;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Motif d'un refus ou d'une suspension : il est transmis au propriétaire.
 */
class ReviewOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
