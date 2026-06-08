<?php

declare(strict_types=1);

namespace App\Http\Controllers\Activity;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\StoreActivityImageRequest;
use App\Http\Requests\Activity\StoreActivityImagesRequest;
use App\Http\Requests\Activity\UploadActivityAvatarRequest;
use App\Http\Resources\ActivityImageResource;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Services\Activity\ActivityImageService;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @tags Activities
 */
class ActivityImageController extends Controller
{
    public function __construct(
        private ActivityImageService $activityImageService
    ) {}

    public function uploadAvatar(UploadActivityAvatarRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('update', $activity);

        $activity = $this->activityImageService->uploadAvatar($activity, $request->file('avatar'));

        return (new ActivityResource($activity))
            ->additional([
                'message' => 'Avatar uploaded successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function index(Activity $activity): JsonResponse
    {
        $this->authorize('view', $activity);

        $images = $activity->getMedia(MediaService::COLLECTION_IMAGES);

        return ActivityImageResource::collection($images)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function store(StoreActivityImageRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('update', $activity);

        $media = $this->activityImageService->addImage($activity, $request->file('image'));

        return (new ActivityImageResource($media))
            ->additional([
                'message' => 'Image added successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function storeBulk(StoreActivityImagesRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('update', $activity);

        $mediaItems = $this->activityImageService->addImages($activity, $request->file('images', []));

        return ActivityImageResource::collection($mediaItems)
            ->additional([
                'message' => 'Images added successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Activity $activity, Media $media): JsonResponse
    {
        $this->authorize('update', $activity);

        if ($media->model_id !== $activity->id || $media->collection_name !== MediaService::COLLECTION_IMAGES) {
            return response()->json([
                'message' => 'Image does not belong to this activity',
                'status' => ApiStatusEnum::ERROR,
                'timestamp' => human_date(now()),
            ], 403);
        }

        $this->activityImageService->deleteImage($activity, $media);

        return response()->json(null, 204);
    }
}
