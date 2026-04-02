<?php

declare(strict_types=1);

namespace App\Http\Requests\Pet;

use App\Models\Pet;
use App\Services\MediaService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StorePetImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $pet = $this->route('pet');

        if (! $pet instanceof Pet) {
            return false;
        }

        $incomingImagesCount = count($this->file('images', []));
        $existingImagesCount = $pet->getMedia(MediaService::COLLECTION_IMAGES)->count();

        if ($existingImagesCount + $incomingImagesCount > 15) {
            throw ValidationException::withMessages([
                'images' => ['This pet has reached the maximum number of images (15).'],
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
