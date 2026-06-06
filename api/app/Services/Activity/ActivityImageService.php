<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Models\Activity;
use App\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ActivityImageService
{
    public function uploadAvatar(Activity $activity, UploadedFile $avatar): Activity
    {
        $activity->addMedia($avatar)
            ->toMediaCollection(MediaService::COLLECTION_AVATAR);

        return $activity->fresh(['address', 'manager', 'collaborators']);
    }

    public function addImage(Activity $activity, UploadedFile $image): Media
    {
        return $activity->addMedia($image)
            ->toMediaCollection(MediaService::COLLECTION_IMAGES);
    }

    public function addImages(Activity $activity, array $images): Collection
    {
        return collect($images)
            ->map(fn (UploadedFile $image): Media => $this->addImage($activity, $image));
    }

    public function deleteImage(Activity $activity, Media $media): void
    {
        $media->delete();
    }
}
