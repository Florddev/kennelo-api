<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Activity;

use App\Enums\AdminActionTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Activity\ListActivityDocumentsRequest;
use App\Http\Requests\Admin\Activity\ReviewActivityRequest;
use App\Http\Resources\ActivityDocumentResource;
use App\Models\ActivityDocument;
use App\Services\Admin\Activity\ActivityReviewService;
use App\Services\Admin\AdminActionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @tags Activities
 */
class ActivityDocumentController extends Controller
{
    public function __construct(
        private readonly ActivityReviewService $reviews,
        private readonly AdminActionService $actions,
    ) {}

    /**
     * List documents to review
     *
     * Du plus ancien au plus récent, pour traiter la file dans l'ordre d'arrivée.
     */
    public function index(ListActivityDocumentsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ActivityDocument::class);

        return ActivityDocumentResource::collection($this->reviews->paginateDocuments($request->validated()));
    }

    public function file(Request $request, ActivityDocument $document): StreamedResponse
    {
        $this->authorize('review', $document);

        $media = $document->getFirstMedia(ActivityDocument::COLLECTION_FILE);

        abort_if($media === null, 404);

        return $media->toInlineResponse($request);
    }

    public function approve(Request $request, ActivityDocument $document): ActivityDocumentResource
    {
        $this->authorize('review', $document);

        $this->reviews->approveDocument($document, $request->user());
        $this->log($request, $document, AdminActionTypeEnum::APPROVE_ACTIVITY_DOCUMENT);

        return new ActivityDocumentResource($document->load(['activity', 'media']));
    }

    public function reject(ReviewActivityRequest $request, ActivityDocument $document): ActivityDocumentResource
    {
        $this->authorize('review', $document);

        $this->reviews->rejectDocument($document, $request->user(), $request->validated('reason'));
        $this->log($request, $document, AdminActionTypeEnum::REJECT_ACTIVITY_DOCUMENT, $request->validated());

        return new ActivityDocumentResource($document->load(['activity', 'media']));
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function log(Request $request, ActivityDocument $document, AdminActionTypeEnum $action, array $metadata = []): void
    {
        $this->actions->log($request->user(), $document->activity?->organization?->owner, $action, [
            'activity_id' => $document->activity_id,
            'document_id' => $document->id,
            ...$metadata,
        ]);
    }
}
