<?php

declare(strict_types=1);

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class ListNotificationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'unread_only' => ['sometimes', 'boolean'],
        ];
    }
}
