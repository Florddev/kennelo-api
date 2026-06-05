<?php

declare(strict_types=1);

namespace App\Http\Requests\Establishment;

use App\Enums\EstablishmentPermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncCollaboratorPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', Rule::in(EstablishmentPermissionEnum::values())],
        ];
    }
}
