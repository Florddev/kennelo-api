<?php

declare(strict_types=1);

namespace App\Http\Requests\Pet;

use App\Models\Pet;
use App\Services\MediaService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StorePetImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pet = $this->route('pet');

        if ($pet instanceof Pet && $pet->getMedia(MediaService::COLLECTION_IMAGES)->count() >= 15) {
            throw ValidationException::withMessages([
                'image' => ['This pet has reached the maximum number of images (15).'],
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
                // 'max:8192',
            ],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        Log::warning('pet_image_upload.validation_failed', [
            'pet_id' => $this->route('pet') instanceof Pet ? (string) $this->route('pet')->id : null,
            'user_id' => $this->user()?->id,
            'errors' => $validator->errors()->toArray(),
            'uploaded_size_bytes' => $this->file('image')?->getSize(),
            'uploaded_mime' => $this->file('image')?->getMimeType(),
            'uploaded_original_name' => $this->file('image')?->getClientOriginalName(),
        ]);

        parent::failedValidation($validator);
    }
}
