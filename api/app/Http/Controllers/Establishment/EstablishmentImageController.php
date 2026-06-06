<?php

declare(strict_types=1);

namespace App\Http\Controllers\Establishment;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Establishment\StoreEstablishmentImageRequest;
use App\Http\Requests\Establishment\StoreEstablishmentImagesRequest;
use App\Http\Requests\Establishment\UploadEstablishmentAvatarRequest;
use App\Http\Resources\EstablishmentImageResource;
use App\Http\Resources\EstablishmentResource;
use App\Models\Establishment;
use App\Services\Establishment\EstablishmentImageService;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @tags Establishments
 */
class EstablishmentImageController extends Controller
{
    public function __construct(
        private EstablishmentImageService $establishmentImageService
    ) {}

    public function uploadAvatar(UploadEstablishmentAvatarRequest $request, Establishment $establishment): JsonResponse
    {
        $this->authorize('update', $establishment);

        $establishment = $this->establishmentImageService->uploadAvatar($establishment, $request->file('avatar'));

        return (new EstablishmentResource($establishment))
            ->additional([
                'message' => 'Avatar uploaded successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function index(Establishment $establishment): JsonResponse
    {
        $this->authorize('view', $establishment);

        $images = $establishment->getMedia(MediaService::COLLECTION_IMAGES);

        return EstablishmentImageResource::collection($images)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function store(StoreEstablishmentImageRequest $request, Establishment $establishment): JsonResponse
    {
        $this->authorize('update', $establishment);

        $media = $this->establishmentImageService->addImage($establishment, $request->file('image'));

        return (new EstablishmentImageResource($media))
            ->additional([
                'message' => 'Image added successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function storeBulk(StoreEstablishmentImagesRequest $request, Establishment $establishment): JsonResponse
    {
        $this->authorize('update', $establishment);

        $mediaItems = $this->establishmentImageService->addImages($establishment, $request->file('images', []));

        return EstablishmentImageResource::collection($mediaItems)
            ->additional([
                'message' => 'Images added successfully',
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Establishment $establishment, Media $media): JsonResponse
    {
        $this->authorize('update', $establishment);

        if ($media->model_id !== $establishment->id || $media->collection_name !== MediaService::COLLECTION_IMAGES) {
            return response()->json([
                'message' => 'Image does not belong to this establishment',
                'status' => ApiStatusEnum::ERROR,
                'timestamp' => human_date(now()),
            ], 403);
        }

        $this->establishmentImageService->deleteImage($establishment, $media);

        return response()->json(null, 204);
    }
}
