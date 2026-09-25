<?php

declare(strict_types=1);

namespace App\Http\Controllers\Activity;

use App\Enums\DocumentTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Activity\StoreActivityDocumentRequest;
use App\Http\Resources\ActivityDocumentResource;
use App\Models\Activity;
use App\Models\ActivityDocument;
use App\Services\Activity\ActivityDocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @tags Activity documents
 */
class ActivityDocumentController extends Controller
{
    public function __construct(
        private readonly ActivityDocumentService $documents,
    ) {}

    /**
     * List the documents
     *
     * Tous les justificatifs déposés, du plus récent au plus ancien, avec leur statut de vérification.
     */
    public function index(Activity $activity): AnonymousResourceCollection
    {
        $this->authorize('update', $activity);

        return ActivityDocumentResource::collection($this->documents->forActivity($activity));
    }

    /**
     * Submit a document
     *
     * Seuls les justificatifs prévus par le métier se déposent. Le dépôt est vérifié par Kennelo.
     */
    public function store(StoreActivityDocumentRequest $request, Activity $activity): ActivityDocumentResource
    {
        $this->authorize('update', $activity);

        return new ActivityDocumentResource($this->documents->submit(
            $activity,
            [
                'document_type' => DocumentTypeEnum::from($request->validated('document_type')),
                'expires_at' => $request->validated('expires_at'),
            ],
            $request->file('file'),
        ));
    }

    /**
     * Download a document
     *
     * Le fichier est stocké sur un disque privé et ne se lit que par cette route.
     */
    public function file(Request $request, Activity $activity, ActivityDocument $document): StreamedResponse
    {
        $this->authorize('update', $activity);

        $media = $document->getFirstMedia(ActivityDocument::COLLECTION_FILE);

        abort_if($media === null, 404);

        return $media->toInlineResponse($request);
    }
}
