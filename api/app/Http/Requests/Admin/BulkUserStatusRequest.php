<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\UserStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['uuid', 'exists:users,id'],
            'status' => ['required', Rule::in([UserStatusEnum::ACTIVE->value, UserStatusEnum::INACTIVE->value])],
        ];
    }
}
