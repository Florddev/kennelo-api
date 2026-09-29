<?php

declare(strict_types=1);

namespace App\Http\Requests\Conversation;

use App\Models\Conversation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Du texte, des pièces jointes, ou les deux. booking_id rattache le message à une réservation du client dans
 * cette activité.
 */
class StoreMessageRequest extends FormRequest
{
    /** Images, PDF, texte, archives et documents bureautiques, 10 Mo au plus chacun. */
    private const array MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
        'text/plain',
        'application/zip',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Conversation|null $conversation */
        $conversation = $this->route('conversation');

        return [
            'content' => ['nullable', 'string', 'max:5000'],
            'booking_id' => [
                'sometimes',
                'nullable',
                'uuid',
                Rule::exists('bookings', 'id')
                    ->where('user_id', $conversation?->user_id)
                    ->where('activity_id', $conversation?->activity_id),
            ],
            'files' => ['sometimes', 'array', 'max:10'],
            'files.*' => ['file', 'max:10240', 'mimetypes:'.implode(',', self::MIME_TYPES)],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled('content') && ! $this->hasFile('files')) {
                    $validator->errors()->add('content', __('conversations.errors.empty_message'));
                }
            },
        ];
    }
}
