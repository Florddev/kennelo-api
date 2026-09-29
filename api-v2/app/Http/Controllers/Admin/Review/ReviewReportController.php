<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Review;

use App\Enums\ReviewReportStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Review\DecideReviewReportRequest;
use App\Http\Requests\Admin\Review\ListReviewReportsRequest;
use App\Http\Resources\ReviewReportResource;
use App\Models\ReviewReport;
use App\Services\Review\ReviewReportService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Reviews
 */
class ReviewReportController extends Controller
{
    public function __construct(private readonly ReviewReportService $reports) {}

    /**
     * List the review reports
     */
    public function index(ListReviewReportsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ReviewReport::class);

        return ReviewReportResource::collection($this->reports->paginate($request->validated()));
    }

    /**
     * Decide on a review report
     *
     * Une seule décision par signalement en attente. removed dépublie l'avis pour de bon et clôt ses autres
     * signalements ; chaque personne qui l'a signalé est prévenue. La décision est tracée dans l'audit.
     */
    public function update(DecideReviewReportRequest $request, ReviewReport $report): ReviewReportResource
    {
        $this->authorize('update', ReviewReport::class);

        return new ReviewReportResource($this->reports->decide($request->user(), $report, ReviewReportStatusEnum::from($request->validated('status'))));
    }
}
