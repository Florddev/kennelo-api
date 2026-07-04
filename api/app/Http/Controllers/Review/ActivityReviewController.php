<?php

declare(strict_types=1);

namespace App\Http\Controllers\Review;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Review\ListReviewsRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Activity;
use App\Services\Review\ReviewService;
use Illuminate\Http\JsonResponse;

class ActivityReviewController extends Controller
{
    public function __construct(
        private ReviewService $reviewService,
    ) {}

    public function index(ListReviewsRequest $request, Activity $activity): JsonResponse
    {
        $reviews = $this->reviewService->forActivity($activity, $request->user(), $request->validated());
        $aggregates = $this->reviewService->aggregatesForActivity($activity);

        return ReviewResource::collection($reviews)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
                'aggregates' => $aggregates,
            ])
            ->response();
    }
}
