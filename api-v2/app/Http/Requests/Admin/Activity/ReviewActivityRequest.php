<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Activity;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Motif d'un refus ou d'une suspension (activité ou justificatif) : il est transmis à l'équipe de l'activité.
 */
class ReviewActivityRequest extends FormRequest
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
