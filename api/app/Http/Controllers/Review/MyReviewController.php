<?php

declare(strict_types=1);

namespace App\Http\Controllers\Review;

use App\Enums\ApiStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Review\ListReviewsRequest;
use App\Http\Resources\ReviewResource;
use App\Services\Review\ReviewService;
use Illuminate\Http\JsonResponse;

class MyReviewController extends Controller
{
    public function __construct(
        private ReviewService $reviewService,
    ) {}

    public function given(ListReviewsRequest $request): JsonResponse
    {
        $reviews = $this->reviewService->mineGiven($request->user(), $request->validated());

        return ReviewResource::collection($reviews)
            ->additional([
                'status' => ApiStatus::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }

    public function received(ListReviewsRequest $request): JsonResponse
    {
        $reviews = $this->reviewService->mineReceived($request->user(), $request->validated());

        return ReviewResource::collection($reviews)
            ->additional([
                'status' => ApiStatus::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }
}
