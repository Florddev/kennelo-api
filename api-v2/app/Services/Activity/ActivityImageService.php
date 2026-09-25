<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Models\Activity;
use App\Services\MediaService;
use App\Services\Subscription\PlanLimitService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Photos d'une activité, dans la limite du quota max_photos de l'offre de l'entreprise.
 */
class ActivityImageService
{
    public function __construct(
        private readonly PlanLimitService $planLimits,
    ) {}

    /**
     * @param  array<int, UploadedFile>  $images
     * @return Collection<int, Media>
     */
    public function add(Activity $activity, array $images): Collection
    {
        $this->planLimits->assertCanAddPhotos($activity, count($images));

        return collect($images)->map(
            fn (UploadedFile $image): Media => $activity->addMedia($image)->toMediaCollection(MediaService::COLLECTION_IMAGES),
        );
    }

    public function delete(Media $media): void
    {
        $media->delete();
    }
}
