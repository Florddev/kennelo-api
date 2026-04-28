<?php

declare(strict_types=1);

namespace App\Services\Establishment;

use App\Models\Establishment;
use App\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class EstablishmentImageService
{
    public function uploadAvatar(Establishment $establishment, UploadedFile $avatar): Establishment
    {
        $establishment->addMedia($avatar)
            ->toMediaCollection(MediaService::COLLECTION_AVATAR);

        return $establishment->fresh(['address', 'manager', 'collaborators']);
    }

    public function addImage(Establishment $establishment, UploadedFile $image): Media
    {
        return $establishment->addMedia($image)
            ->toMediaCollection(MediaService::COLLECTION_IMAGES);
    }

    public function addImages(Establishment $establishment, array $images): Collection
    {
        return collect($images)
            ->map(fn (UploadedFile $image): Media => $this->addImage($establishment, $image));
    }

    public function deleteImage(Establishment $establishment, Media $media): void
    {
        $media->delete();
    }
}
