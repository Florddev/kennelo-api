<?php

declare(strict_types=1);

namespace App\Http\Requests\Activity;

use App\Models\Activity;
use App\Services\MediaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreActivityImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $activity = $this->route('activity');

        if (! $activity instanceof Activity) {
            return false;
        }

        $incomingImagesCount = count($this->file('images', []));
        $existingImagesCount = $activity->getMedia(MediaService::COLLECTION_IMAGES)->count();

        if ($existingImagesCount + $incomingImagesCount > 15) {
            throw ValidationException::withMessages([
                'images' => ['This activity has reached the maximum number of images (15).'],
            ]);
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1'],
            'images.*' => [
                'required',
                'image',
                'mimetypes:image/jpeg,image/jpg,image/png,image/gif,image/webp',
            ],
        ];
    }
}
