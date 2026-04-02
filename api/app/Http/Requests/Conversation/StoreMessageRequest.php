<?php

declare(strict_types=1);

namespace App\Http\Requests\Conversation;

use App\Enums\MessageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:5000'],
            'message_type' => ['sometimes', 'string', Rule::in([
                MessageType::Text->value,
                MessageType::File->value,
                MessageType::BookingReference->value,
            ])],
            'booking_id' => ['sometimes', 'nullable', 'uuid', 'exists:bookings,id'],
        ];
    }
}
