<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Prospect;

use Illuminate\Foundation\Http\FormRequest;

class StoreProspectNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
        ];
    }
}
