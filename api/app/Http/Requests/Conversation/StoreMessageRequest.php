<?php

declare(strict_types=1);

namespace App\Http\Requests\Conversation;

use App\Enums\MessageType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
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
            'content' => ['nullable', 'string', 'max:5000'],
            'message_type' => ['sometimes', 'string', Rule::in([
                MessageType::Text->value,
                MessageType::File->value,
                MessageType::BookingReference->value,
            ])],
            'booking_id' => ['sometimes', 'nullable', 'uuid', 'exists:bookings,id'],
            'files' => ['sometimes', 'nullable', 'array', 'max:10'],
            'files.*' => [
                'mimetypes:image/jpeg,image/jpg,image/png,image/gif,image/webp,application/pdf,text/plain,application/zip,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if (! $this->filled('content') && ! $this->hasFile('files')) {
                $v->errors()->add('content', 'Either content or files is required.');
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        Log::warning('conversation_message.upload_validation_failed', [
            'conversation_id' => $this->route('conversation')?->id,
            'user_id' => $this->user()?->id,
            'errors' => $validator->errors()->toArray(),
            'files' => collect($this->file('files') ?? [])->map(fn ($f) => [
                'name' => $f->getClientOriginalName(),
                'size' => $f->getSize(),
                'mime' => $f->getClientMimeType(),
            ])->toArray(),
        ]);

        parent::failedValidation($validator);
    }
}
