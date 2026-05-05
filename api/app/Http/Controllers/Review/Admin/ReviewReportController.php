<?php

declare(strict_types=1);

namespace App\Http\Controllers\Review\Admin;

use App\Enums\ApiStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Review\ListReviewReportsRequest;
use App\Http\Requests\Review\UpdateReviewReportRequest;
use App\Http\Resources\ReviewReportResource;
use App\Models\ReviewReport;
use App\Services\Review\ReviewReportService;
use Illuminate\Http\JsonResponse;

class ReviewReportController extends Controller
{
    public function __construct(
        private ReviewReportService $reportService,
    ) {}

    public function index(ListReviewReportsRequest $request): JsonResponse
    {
        $this->authorize('manage', ReviewReport::class);

        $reports = $this->reportService->listAll($request->validated());

        return ReviewReportResource::collection($reports)
            ->additional([
                'status' => ApiStatus::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function update(UpdateReviewReportRequest $request, ReviewReport $report): JsonResponse
    {
        $this->authorize('manage', ReviewReport::class);

        $report = $this->reportService->updateStatus($report, $request->validated()['status']);

        return (new ReviewReportResource($report))
            ->additional([
                'status' => ApiStatus::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }
}
