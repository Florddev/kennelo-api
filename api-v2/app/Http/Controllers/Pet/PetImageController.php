<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pet;

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
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @tags Pets
 */
class PetImageController extends Controller
{
    public function __construct(
        private PetService $petService
    ) {}

    public function uploadAvatar(UploadPetAvatarRequest $request, Pet $pet): PetResource
    {
        $this->authorize('update', $pet);

        return new PetResource($this->petService->uploadAvatar($pet, $request->file('avatar')));
    }

    public function index(Pet $pet): AnonymousResourceCollection
    {
        $this->authorize('view', $pet);

        return PetImageResource::collection($pet->getMedia(MediaService::COLLECTION_IMAGES));
    }

    public function store(StorePetImageRequest $request, Pet $pet): PetImageResource
    {
        $this->authorize('update', $pet);

        return new PetImageResource($this->petService->addImage($pet, $request->file('image')));
    }

    public function storeBulk(StorePetImagesRequest $request, Pet $pet): JsonResponse
    {
        $this->authorize('update', $pet);

        $mediaItems = $this->petService->addImages($pet, $request->file('images', []));

        return PetImageResource::collection($mediaItems)->response()->setStatusCode(201);
    }

    /**
     * La route lie l'image à l'animal (scopeBindings) : une image d'un autre animal donne une 404.
     */
    public function destroy(Pet $pet, Media $media): Response
    {
        $this->authorize('update', $pet);

        abort_unless($media->collection_name === MediaService::COLLECTION_IMAGES, 404);

        $this->petService->deleteImage($pet, $media);

        return response()->noContent();
    }
}
