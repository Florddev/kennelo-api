<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pet;

use App\Enums\ApiStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pet\StorePetImageRequest;
use App\Http\Requests\Pet\StorePetImagesRequest;
use App\Http\Requests\Pet\UploadPetAvatarRequest;
use App\Http\Resources\PetImageResource;
use App\Http\Resources\PetResource;
use App\Models\Pet;
use App\Services\MediaService;
use App\Services\Pet\PetService;
use Illuminate\Http\JsonResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @tags Pets
 */
class PetImageController extends Controller
{
    public function __construct(
        private PetService $petService
    ) {}

    public function uploadAvatar(UploadPetAvatarRequest $request, Pet $pet): JsonResponse
    {
        $this->authorize('update', $pet);

        $pet = $this->petService->uploadAvatar($pet, $request->file('avatar'));

        return (new PetResource($pet))
            ->additional([
                'message' => 'Avatar uploaded successfully',
                'status' => ApiStatus::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function index(Pet $pet): JsonResponse
    {
        $this->authorize('view', $pet);

        $images = $pet->getMedia(MediaService::COLLECTION_IMAGES);

        return PetImageResource::collection($images)
            ->additional([
                'status' => ApiStatus::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function store(StorePetImageRequest $request, Pet $pet): JsonResponse
    {
        $this->authorize('update', $pet);

        $media = $this->petService->addImage($pet, $request->file('image'));

        return (new PetImageResource($media))
            ->additional([
                'message' => 'Image added successfully',
                'status' => ApiStatus::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function storeBulk(StorePetImagesRequest $request, Pet $pet): JsonResponse
    {
        $this->authorize('update', $pet);

        $mediaItems = $this->petService->addImages($pet, $request->file('images', []));

        return PetImageResource::collection($mediaItems)
            ->additional([
                'message' => 'Images added successfully',
                'status' => ApiStatus::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Pet $pet, Media $media): JsonResponse
    {
        $this->authorize('update', $pet);

        if ($media->model_id !== $pet->id || $media->collection_name !== MediaService::COLLECTION_IMAGES) {
            return response()->json([
                'message' => 'Image does not belong to this pet',
                'status' => ApiStatus::ERROR,
                'timestamp' => human_date(now()),
            ], 403);
        }

        $this->petService->deleteImage($pet, $media);

        return response()->json(null, 204);
    }
}
