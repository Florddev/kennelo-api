<?php

declare(strict_types=1);

namespace App\Http\Requests\Establishment;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class UploadEstablishmentAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'image',
                'mimetypes:image/jpeg,image/jpg,image/png,image/gif,image/webp',
            ],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        Log::warning('establishment_avatar_upload.validation_failed', [
            'establishment_id' => $this->route('establishment')?->id,
            'user_id' => $this->user()?->id,
            'errors' => $validator->errors()->toArray(),
            'uploaded_size_bytes' => $this->file('avatar')?->getSize(),
            'uploaded_mime' => $this->file('avatar')?->getMimeType(),
            'uploaded_original_name' => $this->file('avatar')?->getClientOriginalName(),
        ]);

        parent::failedValidation($validator);
    }
}
