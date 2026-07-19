<?php

declare(strict_types=1);

namespace App\Http\Requests\Activity;

use App\Models\Activity;
use App\Services\MediaService;
use App\Services\Subscription\PlanLimitService;
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

        $maxPhotos = app(PlanLimitService::class)->maxPhotos($activity);

        if ($maxPhotos === null || $maxPhotos < 0) {
            return true;
        }

        $incomingImagesCount = count($this->file('images', []));
        $existingImagesCount = $activity->getMedia(MediaService::COLLECTION_IMAGES)->count();

        if ($existingImagesCount + $incomingImagesCount > $maxPhotos) {
            throw ValidationException::withMessages([
                'images' => [__('plans.limit_reached.photos', ['limit' => (string) $maxPhotos])],
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
                'mimetypes:image/jpeg,image/jpg,image/png,image/webp',
                'max:8192',
            ],
        ];
    }
}
