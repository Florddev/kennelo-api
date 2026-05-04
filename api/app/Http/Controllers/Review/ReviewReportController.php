<?php

declare(strict_types=1);

namespace App\Http\Controllers\Review;

use App\Enums\ApiStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Review\StoreReviewReportRequest;
use App\Http\Resources\ReviewReportResource;
use App\Models\Review;
use App\Services\Review\ReviewReportService;
use Illuminate\Http\JsonResponse;

class ReviewReportController extends Controller
{
    public function __construct(
        private ReviewReportService $reportService,
    ) {}

    public function store(StoreReviewReportRequest $request, Review $review): JsonResponse
    {
        $this->authorize('report', $review);

        $report = $this->reportService->create($request->user(), $review, $request->validated());

        return (new ReviewReportResource($report))
            ->additional([
                'status' => ApiStatus::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response()
            ->setStatusCode(201);
    }
}
