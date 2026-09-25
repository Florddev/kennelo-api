<?php

declare(strict_types=1);

namespace App\Http\Controllers\Activity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\StoreActivityImagesRequest;
use App\Http\Resources\ImageResource;
use App\Models\Activity;
use App\Services\Activity\ActivityImageService;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @tags Activities
 */
class ActivityImageController extends Controller
{
    public function __construct(
        private readonly ActivityImageService $images,
    ) {}

    /**
     * Add photos
     *
     * Jusqu'à dix photos par envoi, dans la limite du quota de photos par activité de l'offre.
     */
    public function store(StoreActivityImagesRequest $request, Activity $activity): JsonResponse
    {
        $this->authorize('update', $activity);

        return ImageResource::collection($this->images->add($activity, $request->file('images', [])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Delete a photo
     *
     * La route lie la photo à l'activité (scopeBindings) : une photo d'une autre activité donne une 404.
     */
    public function destroy(Activity $activity, Media $media): Response
    {
        $this->authorize('update', $activity);

        abort_unless($media->collection_name === MediaService::COLLECTION_IMAGES, 404);

        $this->images->delete($media);

        return response()->noContent();
    }
}
