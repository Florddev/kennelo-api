<?php

declare(strict_types=1);

namespace App\Http\Requests\Activity;

use App\Models\Activity;
use App\Services\MediaService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StoreActivityImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $activity = $this->route('activity');

        if ($activity instanceof Activity && $activity->getMedia(MediaService::COLLECTION_IMAGES)->count() >= 15) {
            throw ValidationException::withMessages([
                'image' => ['This activity has reached the maximum number of images (15).'],
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
                'max:8192',
            ],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        Log::warning('activity_image_upload.validation_failed', [
            'activity_id' => $this->route('activity') instanceof Activity ? (string) $this->route('activity')->id : null,
            'user_id' => $this->user()?->id,
            'errors' => $validator->errors()->toArray(),
            'uploaded_size_bytes' => $this->file('image')?->getSize(),
            'uploaded_mime' => $this->file('image')?->getMimeType(),
            'uploaded_original_name' => $this->file('image')?->getClientOriginalName(),
        ]);

        parent::failedValidation($validator);
    }
}
