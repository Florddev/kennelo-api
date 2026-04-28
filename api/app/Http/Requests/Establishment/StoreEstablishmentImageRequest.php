<?php

declare(strict_types=1);

namespace App\Http\Requests\Establishment;

use App\Models\Establishment;
use App\Services\MediaService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StoreEstablishmentImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $establishment = $this->route('establishment');

        if ($establishment instanceof Establishment && $establishment->getMedia(MediaService::COLLECTION_IMAGES)->count() >= 15) {
            throw ValidationException::withMessages([
                'image' => ['This establishment has reached the maximum number of images (15).'],
            ]);
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'image' => [
                'required',
                'image',
                'mimetypes:image/jpeg,image/jpg,image/png,image/gif,image/webp',
            ],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        Log::warning('establishment_image_upload.validation_failed', [
            'establishment_id' => $this->route('establishment') instanceof Establishment ? (string) $this->route('establishment')->id : null,
            'user_id' => $this->user()?->id,
            'errors' => $validator->errors()->toArray(),
            'uploaded_size_bytes' => $this->file('image')?->getSize(),
            'uploaded_mime' => $this->file('image')?->getMimeType(),
            'uploaded_original_name' => $this->file('image')?->getClientOriginalName(),
        ]);

        parent::failedValidation($validator);
    }
}
